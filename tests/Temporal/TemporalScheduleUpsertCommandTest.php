<?php

namespace Rr\Bundle\Workers\Tests\Temporal;

use PHPUnit\Framework\TestCase;
use Rr\Bundle\Workers\Temporal\Commands\TemporalScheduleUpsertCommand;
use Rr\Bundle\Workers\Temporal\Contracts\Services\Cron\CronMapInterface;
use Rr\Bundle\Workers\Temporal\Services\Cron\CronJob;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Google\Protobuf\Timestamp;
use Temporal\Api\Schedule\V1\Schedule as ScheduleProto;
use Temporal\Api\Schedule\V1\ScheduleInfo as ScheduleInfoProto;
use Temporal\Api\Workflowservice\V1\DeleteScheduleRequest;
use Temporal\Api\Workflowservice\V1\DeleteScheduleResponse;
use Temporal\Api\Workflowservice\V1\DescribeScheduleResponse;
use Temporal\Api\Workflowservice\V1\UpdateScheduleResponse;
use Temporal\Client\Common\Paginator;
use Temporal\Client\GRPC\Context;
use Temporal\Client\GRPC\ServiceClientInterface;
use Temporal\Client\Schedule\Action\StartWorkflowAction;
use Temporal\Client\Schedule\Schedule;
use Temporal\Client\Schedule\ScheduleHandle;
use Temporal\Client\ScheduleClient;
use Temporal\Client\ScheduleClientInterface;

final class TemporalScheduleUpsertCommandTest extends TestCase
{
    public function testEmptyCronMapIsNotAnError(): void
    {
        $scheduleClient = $this->createMock(ScheduleClientInterface::class);
        $scheduleClient->expects(self::never())->method('createSchedule');
        $scheduleClient->expects(self::never())->method('listSchedules');

        $tester = $this->execute($this->cronMap([]), $scheduleClient);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Cron map empty', $tester->getDisplay());
    }

    public function testJobWithoutTheInterfaceStopsTheCommand(): void
    {
        $scheduleClient = $this->createMock(ScheduleClientInterface::class);
        $scheduleClient->expects(self::never())->method('createSchedule');

        $tester = $this->execute($this->cronMap([new \stdClass()]), $scheduleClient);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('CronJobInterface', $tester->getDisplay());
    }

    public function testUnknownScheduleIsCreatedFromTheCronJob(): void
    {
        $created = null;

        $scheduleClient = $this->createMock(ScheduleClientInterface::class);
        $scheduleClient->method('getHandle')->willReturn($this->handle('cron_job_report', updateFails: true));
        $scheduleClient->method('listSchedules')->willReturn($this->schedules());
        $scheduleClient->expects(self::once())
            ->method('createSchedule')
            ->willReturnCallback(function (Schedule $schedule, $options, ?string $id) use (&$created): ScheduleHandle {
                $created = [$schedule, $id];

                return $this->handle((string)$id);
            });

        $tester = $this->execute(
            $this->cronMap([new CronJob('report', '0 * * * *', new SendEmail(42), 'heavy')]),
            $scheduleClient,
        );

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('created', $tester->getDisplay());

        [$schedule, $id] = $created;
        self::assertSame('cron_job_report', $id);

        $action = $schedule->action;
        self::assertInstanceOf(StartWorkflowAction::class, $action);
        self::assertSame('heavy', $action->taskQueue->name);
        self::assertSame([SendEmail::class, ['id' => 42]], $action->input->getValues());
        self::assertSame(['0 * * * *'], $schedule->spec->cronStringList);
        self::assertSame('UTC', $schedule->spec->timezoneName);
    }

    public function testExistingScheduleIsUpdatedInsteadOfCreated(): void
    {
        $scheduleClient = $this->createMock(ScheduleClientInterface::class);
        $scheduleClient->method('getHandle')->willReturn($this->handle('cron_job_report'));
        $scheduleClient->method('listSchedules')->willReturn($this->schedules());
        $scheduleClient->expects(self::never())->method('createSchedule');

        $tester = $this->execute(
            $this->cronMap([new CronJob('report', '0 * * * *', new SendEmail(42))]),
            $scheduleClient,
        );

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('updated', $tester->getDisplay());
    }

    public function testOnlyOwnAndUnwantedSchedulesAreDeleted(): void
    {
        $deleted = [];

        $scheduleClient = $this->createMock(ScheduleClientInterface::class);
        $scheduleClient->method('listSchedules')->willReturn($this->schedules(
            'cron_job_report',   // нужен — остаётся
            'cron_job_stale',    // наш, но не в карте — удаляем
            'someone_else',      // чужой — не трогаем
        ));
        $scheduleClient->method('getHandle')->willReturnCallback(
            function (string $id) use (&$deleted): ScheduleHandle {
                return $this->handle($id, $deleted);
            }
        );

        $tester = $this->execute(
            $this->cronMap([new CronJob('report', '0 * * * *', new SendEmail(42))]),
            $scheduleClient,
        );

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertSame(['cron_job_stale'], $deleted);
        self::assertStringContainsString('deleted', $tester->getDisplay());
    }

    /**
     * Реальный ScheduleHandle (класс final) поверх замоканного gRPC-клиента.
     *
     * @param string $id
     * @param array|null $deleted Сюда попадают id, для которых вызвали delete()
     * @param bool $updateFails Сымитировать отсутствующее расписание
     * @return ScheduleHandle
     */
    private function handle(string $id, ?array &$deleted = null, bool $updateFails = false): ScheduleHandle
    {
        $serviceClient = $this->createMock(ServiceClientInterface::class);
        // ScheduleClient оборачивает контекст при старте — отдаём тот же мок
        $serviceClient->method('getContext')->willReturn(Context::default());
        $serviceClient->method('withContext')->willReturnSelf();

        $serviceClient->method('DescribeSchedule')->willReturnCallback(
            fn(): DescribeScheduleResponse => $updateFails
                ? throw new \RuntimeException('schedule not found')
                : (new DescribeScheduleResponse())
                    ->setConflictToken('token')
                    ->setSchedule(new ScheduleProto())
                    ->setInfo((new ScheduleInfoProto())->setCreateTime((new Timestamp())->setSeconds(1)))
        );

        $serviceClient->method('UpdateSchedule')->willReturn(new UpdateScheduleResponse());
        $serviceClient->method('DeleteSchedule')->willReturnCallback(
            function (DeleteScheduleRequest $request) use (&$deleted): object {
                $deleted[] = $request->getScheduleId();

                return new DeleteScheduleResponse();
            }
        );

        return ScheduleClient::create($serviceClient)->getHandle($id);
    }

    /**
     * @param string ...$ids
     * @return Paginator
     */
    private function schedules(string ...$ids): Paginator
    {
        $entries = array_map(static fn(string $id): object => (object)['scheduleId' => $id], $ids);

        return Paginator::createFromGenerator((static function () use ($entries) { yield $entries; })(), null);
    }

    /**
     * @param array $jobs
     * @return CronMapInterface
     */
    private function cronMap(array $jobs): CronMapInterface
    {
        $map = $this->createMock(CronMapInterface::class);
        $map->method('getAll')->willReturn($jobs);

        return $map;
    }

    /**
     * @param CronMapInterface $cronMap
     * @param ScheduleClientInterface $scheduleClient
     * @return CommandTester
     */
    private function execute(CronMapInterface $cronMap, ScheduleClientInterface $scheduleClient): CommandTester
    {
        $tester = new CommandTester(
            new TemporalScheduleUpsertCommand($cronMap, $scheduleClient, new Serializer([new ObjectNormalizer()]))
        );
        $tester->execute([]);

        return $tester;
    }
}