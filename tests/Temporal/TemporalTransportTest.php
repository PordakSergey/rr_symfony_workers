<?php

namespace Rr\Bundle\Workers\Tests\Temporal;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Rr\Bundle\Workers\Jobs\Response\JobResponse;
use Rr\Bundle\Workers\Temporal\Services\JobsDispatcher\TemporalJobDispatcher;
use Rr\Bundle\Workers\Temporal\SymfonyIntegration\Messenger\Factories\TransportFactory;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\TransportMessageIdStamp;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

final class TemporalTransportTest extends TestCase
{
    public static function dsnProvider(): iterable
    {
        yield 'default' => ['temporal://default', null, 'messenger'];
        yield 'no host' => ['temporal://', null, 'messenger'];
        yield 'queue and tag' => ['temporal://heavy?tag=report', 'heavy', 'report'];
    }

    #[DataProvider('dsnProvider')]
    public function testSendStartsWorkflowWithQueueAndTagFromDsn(string $dsn, ?string $queue, string $tag): void
    {
        $message = new SendEmail(42);

        $dispatcher = $this->createMock(TemporalJobDispatcher::class);
        $dispatcher->expects(self::once())
            ->method('dispatch')
            ->with($message, false, $tag, $queue, 0)
            ->willReturn(new JobResponse('wf-1', null));

        $factory = new TransportFactory($dispatcher);
        $envelope = $factory->createTransport($dsn, [], $this->createStub(SerializerInterface::class))
            ->send(new Envelope($message));

        self::assertSame('wf-1', $envelope->last(TransportMessageIdStamp::class)?->getId());
    }

    public function testSendPassesDelayStamp(): void
    {
        $message = new SendEmail(42);

        $dispatcher = $this->createMock(TemporalJobDispatcher::class);
        $dispatcher->expects(self::once())
            ->method('dispatch')
            ->with($message, false, 'messenger', null, 5000)
            ->willReturn(new JobResponse('wf-1', null));

        (new TransportFactory($dispatcher))
            ->createTransport('temporal://default', [], $this->createStub(SerializerInterface::class))
            ->send(new Envelope($message, [new DelayStamp(5000)]));
    }

    public function testSupportsOnlyTemporalDsn(): void
    {
        $factory = new TransportFactory($this->createStub(TemporalJobDispatcher::class));

        self::assertTrue($factory->supports('temporal://default', []));
        self::assertFalse($factory->supports('redis://localhost', []));
    }
}