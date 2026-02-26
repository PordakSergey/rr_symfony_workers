<?php

namespace Rr\Bundle\Workers\Temporal\Commands;

use Rr\Bundle\Workers\Temporal\Contracts\Services\Cron\CronJobInterface;
use Rr\Bundle\Workers\Temporal\Contracts\Services\Cron\CronMapInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Temporal\Client\Schedule\Action\StartWorkflowAction;
use Temporal\Client\Schedule\Schedule;
use Temporal\Client\Schedule\ScheduleOptions;
use Temporal\Client\Schedule\Spec\ScheduleSpec;
use Temporal\Client\Schedule\Update\ScheduleUpdate;
use Temporal\Client\ScheduleClientInterface;

#[AsCommand(name: "temporal:schedule:upsert", description: "Upsert temporal schedule.")]
class TemporalScheduleUpsertCommand extends Command
{
    protected const string TASK_PREFIX = 'cron_job_';

    /**
     * @param CronMapInterface $cronMap
     * @param ScheduleClientInterface $scheduleClient
     * @param SerializerInterface $serializer
     */
    public function __construct(
        protected CronMapInterface        $cronMap,
        protected ScheduleClientInterface $scheduleClient,
        protected SerializerInterface     $serializer,

    )
    {
        parent::__construct();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws ExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if(empty($this->cronMap->getAll())){
            $output->writeln("  <comment>Cron map empty</comment>");
            return Command::SUCCESS;
        }

        $wantedIds = [];
        foreach ($this->cronMap->getAll() as $cronJob) {
            $taskId = self::TASK_PREFIX . $cronJob->getTaskId();

            if (!is_a($cronJob, CronJobInterface::class)) {
                $output->writeln("  <comment>Command job must be implemented CronJobInterface</comment>");
                return Command::FAILURE;
            }

            $wantedIds[$taskId] = true;

            $args = [
                $cronJob->getCommand()::class,
                json_decode($this->serializer->serialize($cronJob->getCommand(), 'json'), true)
            ];

            $action = StartWorkflowAction::new('run')
                ->withTaskQueue($cronJob->getTaskQueue())
                ->withInput($args);

            $spec = ScheduleSpec::new()
                ->withTimezoneName($cronJob->getTimezone())
                ->withAddedCronString($cronJob->getCron());

            $schedule = Schedule::new()
                ->withAction($action)
                ->withSpec($spec);

            $handle = $this->scheduleClient->getHandle($taskId);

            try {
                $handle->update(function ($input) use ($schedule) {
                    return ScheduleUpdate::new($schedule);
                });
                $output->writeln("  <comment>updated</comment>");
                continue;
            } catch (\Throwable $exception) {
                $options = ScheduleOptions::new();

                $this->scheduleClient->listSchedules();

                $this->scheduleClient->createSchedule($schedule, $options, $taskId);
                $output->writeln("  <comment>created</comment>");
            }
        }

        foreach ($this->scheduleClient->listSchedules() as $listed) {
            if (strncmp($listed->scheduleId, self::TASK_PREFIX, strlen(self::TASK_PREFIX)) !== 0) {
                continue;
            }

            if (!isset($wantedIds[$listed->scheduleId])) {
                $this->scheduleClient->getHandle($listed->scheduleId)->delete();
                $output->writeln("  <comment>deleted</comment> {$listed->scheduleId}");
            }
        }

        return Command::SUCCESS;
    }
}