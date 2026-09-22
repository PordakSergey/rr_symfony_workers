<?php

namespace Rr\Bundle\Workers\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Rr\Bundle\Workers\DependencyInjection\Configuration;
use Rr\Bundle\Workers\Temporal\Services\Activities\MessengerActivityOptions;
use Rr\Bundle\Workers\Workers\TemporalWorker;
use Symfony\Component\Config\Definition\Processor;

final class ConfigurationTest extends TestCase
{
    public function testDefaults(): void
    {
        $config = $this->process([]);

        self::assertSame('default', $config['jobs']['default_queue']);
        self::assertSame(['default' => []], $config['jobs']['queues']);
        self::assertSame(TemporalWorker::DEFAULT_TASK_QUEUE, $config['temporal']['default_queue']);
        self::assertSame(
            [
                'start_to_close_timeout' => MessengerActivityOptions::START_TO_CLOSE_TIMEOUT,
                'maximum_attempts' => MessengerActivityOptions::MAXIMUM_ATTEMPTS,
                'initial_interval' => MessengerActivityOptions::INITIAL_INTERVAL,
            ],
            $config['temporal']['activity'],
        );
    }

    public function testActivityOptionsAreOverridableOneByOne(): void
    {
        $config = $this->process(['temporal' => ['activity' => ['maximum_attempts' => 0]]]);

        self::assertSame(0, $config['temporal']['activity']['maximum_attempts'], '0 — бесконечные попытки');
        self::assertSame(
            MessengerActivityOptions::START_TO_CLOSE_TIMEOUT,
            $config['temporal']['activity']['start_to_close_timeout'],
        );
    }

    public function testQueueOptionsGetFilledIn(): void
    {
        $config = $this->process([
            'jobs' => [
                'default_queue' => 'flights.storage',
                'queues' => [
                    'flights.storage' => ['priority' => 10],
                    'slow' => null,
                ],
            ],
        ]);

        self::assertSame('flights.storage', $config['jobs']['default_queue']);
        self::assertSame(
            [
                'flights.storage' => ['priority' => 10, 'delay' => 0, 'auto_ack' => false],
                'slow' => ['delay' => 0, 'priority' => 0, 'auto_ack' => false],
            ],
            $config['jobs']['queues'],
        );
    }

    public function testDebugDropsActivityRetries(): void
    {
        $config = (new Processor())->processConfiguration(new Configuration(true), [[]]);

        self::assertSame(1, $config['temporal']['activity']['maximum_attempts']);
        self::assertSame(3600, $config['temporal']['activity']['start_to_close_timeout']);
        self::assertSame(5, (new Processor())
            ->processConfiguration(new Configuration(true), [['temporal' => ['activity' => ['maximum_attempts' => 5]]]])
            ['temporal']['activity']['maximum_attempts'], 'явный конфиг сильнее debug-дефолта');
    }

    private function process(array $config): array
    {
        return (new Processor())->processConfiguration(new Configuration(), [$config]);
    }
}