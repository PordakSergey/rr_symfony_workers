<?php

namespace Rr\Bundle\Workers\Tests\Temporal;

use PHPUnit\Framework\TestCase;
use Rr\Bundle\Workers\Temporal\Factories\TemporalClientServiceFactory;

final class TemporalClientServiceFactoryTest extends TestCase
{
    private array $env;
    private array $server;

    protected function setUp(): void
    {
        $this->env = $_ENV;
        $this->server = $_SERVER;
    }

    protected function tearDown(): void
    {
        $_ENV = $this->env;
        $_SERVER = $this->server;
    }

    public function testFallsBackToTheDefaultTemporalAddress(): void
    {
        unset($_ENV['TEMPORAL_URL'], $_SERVER['TEMPORAL_URL']);

        self::assertSame(TemporalClientServiceFactory::DEFAULT_ADDRESS, TemporalClientServiceFactory::address());
    }

    public function testEnvWins(): void
    {
        $_ENV['TEMPORAL_URL'] = 'temporal:7233';
        $_SERVER['TEMPORAL_URL'] = 'ignored:7233';

        self::assertSame('temporal:7233', TemporalClientServiceFactory::address());
    }

    public function testServerIsUsedWhenEnvIsMissing(): void
    {
        unset($_ENV['TEMPORAL_URL']);
        $_SERVER['TEMPORAL_URL'] = 'temporal:7233';

        self::assertSame('temporal:7233', TemporalClientServiceFactory::address());
    }
}