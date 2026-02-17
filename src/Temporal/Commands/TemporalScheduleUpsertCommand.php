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

        foreach ($this->cronMap->getAll() as $scheduleId => $cronJob) {
            if (!is_a($cronJob, CronJobInterface::class)) {
                $output->writeln("  <comment>Command job must be implemented CronJobInterface</comment>");
                return Command::FAILURE;
            }

            $args = [$cronJob->getCommand()::class, $this->serializer->serialize($cronJob->getCommand(), "json")];

            $action = StartWorkflowAction::new($cronJob->getWorkflowType())
                ->withTaskQueue($cronJob->getTaskQueue())
                ->withInput($args);

            $spec = ScheduleSpec::new()
                ->withTimezoneName($cronJob->getTimezone())
                ->withAddedCronString($cronJob->getCron());

            $schedule = Schedule::new()
                ->withAction($action)
                ->withSpec($spec);

            $handle = $this->scheduleClient->getHandle($scheduleId);

            try {
                $handle->update(function ($input) use ($schedule) {
                    return ScheduleUpdate::new($schedule);
                });
                $output->writeln("  <comment>updated</comment>");
                continue;
            } catch (\Throwable $exception) {
                $options = ScheduleOptions::new();

                $this->scheduleClient->listSchedules();

                $this->scheduleClient->createSchedule($schedule, $options, $scheduleId);
                $output->writeln("  <comment>created</comment>");
            }
        }

        return Command::SUCCESS;
    }
}