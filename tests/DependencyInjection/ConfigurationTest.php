<?php

namespace Rr\Bundle\Workers\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Rr\Bundle\Workers\DependencyInjection\Configuration;
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

    private function process(array $config): array
    {
        return (new Processor())->processConfiguration(new Configuration(), [$config]);
    }
}