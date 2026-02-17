<?php

namespace Rr\Bundle\Workers\Temporal\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
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
     * @param ScheduleClientInterface $scheduleClient
     */
    public function __construct(
        protected ScheduleClientInterface $scheduleClient,
    )
    {
        parent::__construct();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach (CronJobMap::all() as $scheduleId => $cfg) {
            $action = StartWorkflowAction::new($cfg['workflowType'])
                ->withTaskQueue($cfg['taskQueue'])
                ->withInput($cfg['args'])
            ;

            $spec = ScheduleSpec::new()
                ->withTimezoneName($cfg['timezone'])
                ->withAddedCronString($cfg['cron']);

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