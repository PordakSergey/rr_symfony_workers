<?php

namespace Rr\Bundle\Workers\Jobs\Services\JobsDispatcher;

use Spiral\RoadRunner\Jobs\JobsInterface;
use Spiral\RoadRunner\Jobs\Options;
use Spiral\RoadRunner\Jobs\OptionsInterface;
use Spiral\RoadRunner\Jobs\QueueInterface;
use Spiral\RoadRunner\Jobs\Task\PreparedTaskInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Пушит команды в RR jobs-пайплайны. Результата у задачи нет — только id,
 * ответ забирает воркер через messenger.
 */
class RrJobDispatcher
{
    /**
     * @param JobsInterface $jobs
     * @param SerializerInterface $serializer
     * @param string $defaultQueue From rr_workers.jobs.default_queue
     * @param array<string, array> $queues Pipeline name => default options, from rr_workers.jobs.queues
     */
    public function __construct(
        protected JobsInterface       $jobs,
        protected SerializerInterface $serializer,
        protected string              $defaultQueue = 'default',
        protected array               $queues = [],
    )
    {
    }

    /**
     * @param object $command
     * @param string|null $queue RoadRunner pipeline name, null means the default one
     * @param OptionsInterface|null $options Delay / priority / auto-ack
     * @return string Task id
     */
    public function push(object $command, ?string $queue = null, ?OptionsInterface $options = null): string
    {
        return $this->pipeline($queue)
            ->push($command::class, $this->serializer->serialize($command, 'json'), $options)
            ->getId();
    }

    /**
     * @param object[] $commands
     * @param string|null $queue
     * @param OptionsInterface|null $options
     * @return string[] Task ids
     */
    public function pushMany(array $commands, ?string $queue = null, ?OptionsInterface $options = null): array
    {
        $pipeline = $this->pipeline($queue);

        $tasks = array_map(
            fn(object $command): PreparedTaskInterface => $pipeline->create(
                $command::class,
                $this->serializer->serialize($command, 'json'),
                $options
            ),
            $commands
        );

        $ids = [];
        foreach ($pipeline->dispatchMany(...$tasks) as $task) {
            $ids[] = $task->getId();
        }

        return $ids;
    }

    /**
     * Опции очереди из конфига; переданные в push() перекрывают их (Options::mergeOptional).
     *
     * @param string|null $queue Null means rr_workers.jobs.default_queue
     * @return QueueInterface
     */
    protected function pipeline(?string $queue): QueueInterface
    {
        $name = $queue ?? $this->defaultQueue;
        $options = $this->queues[$name] ?? [];

        return $this->jobs->connect($name, $options ? new Options(
            $options['delay'] ?? Options::DEFAULT_DELAY,
            $options['priority'] ?? Options::DEFAULT_PRIORITY,
            $options['auto_ack'] ?? Options::DEFAULT_AUTO_ACK,
        ) : null);
    }
}