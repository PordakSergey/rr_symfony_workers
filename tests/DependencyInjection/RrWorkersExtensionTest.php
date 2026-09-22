<?php

namespace Rr\Bundle\Workers\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Rr\Bundle\Workers\DependencyInjection\RrWorkersExtension;
use Rr\Bundle\Workers\Jobs\Services\JobsDispatcher\RrJobDispatcher;
use Rr\Bundle\Workers\Temporal\Services\JobsDispatcher\TemporalJobDispatcher;
use Rr\Bundle\Workers\Workers\TemporalWorker;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class RrWorkersExtensionTest extends TestCase
{
    public function testConfigReachesTheServiceDefinitions(): void
    {
        $container = $this->load([
            'jobs' => ['default_queue' => 'flights.storage', 'queues' => ['flights.storage' => ['priority' => 10]]],
            'temporal' => [
                'default_queue' => 'heavy',
                'activity' => ['start_to_close_timeout' => 30, 'maximum_attempts' => 5],
            ],
        ]);

        $jobs = $container->getDefinition(RrJobDispatcher::class);
        self::assertSame('flights.storage', $jobs->getArgument('$defaultQueue'));
        self::assertSame(
            ['flights.storage' => ['priority' => 10, 'delay' => 0, 'auto_ack' => false]],
            $jobs->getArgument('$queues'),
        );

        self::assertSame('heavy', $container->getDefinition(TemporalJobDispatcher::class)->getArgument('$taskQueue'));
        self::assertFalse($container->getDefinition(TemporalJobDispatcher::class)->getArgument('$debug'));
        self::assertSame(
            ['start_to_close_timeout' => 30, 'maximum_attempts' => 5, 'initial_interval' => 3],
            $container->getDefinition(TemporalWorker::class)->getArgument('$activity'),
        );
    }

    public function testDebugKillsRetries(): void
    {
        $container = $this->load([], debug: true);

        self::assertSame(
            ['start_to_close_timeout' => 3600, 'maximum_attempts' => 1, 'initial_interval' => 3],
            $container->getDefinition(TemporalWorker::class)->getArgument('$activity'),
        );
        self::assertTrue($container->getDefinition(TemporalJobDispatcher::class)->getArgument('$debug'));
    }

    public function testKvAdaptersAreRegisteredPerStorage(): void
    {
        $container = $this->load(['kv' => ['storages' => ['local', 'redis']]]);

        self::assertTrue($container->hasDefinition('cache.adapter.roadrunner.kv_local'));
        self::assertTrue($container->hasDefinition('cache.adapter.roadrunner.kv_redis'));
    }

    public function testNoKvAdaptersWithoutStorages(): void
    {
        self::assertSame(
            [],
            array_filter(
                array_keys($this->load([])->getDefinitions()),
                static fn(string $id): bool => str_starts_with($id, 'cache.adapter.roadrunner.'),
            ),
        );
    }

    /**
     * @param array $config
     * @return ContainerBuilder
     */
    private function load(array $config, bool $debug = false): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', $debug);
        (new RrWorkersExtension())->load([$config], $container);

        return $container;
    }
}