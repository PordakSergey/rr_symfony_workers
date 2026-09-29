<?php

namespace Rr\Bundle\Workers\Tests\Temporal;

use PHPUnit\Framework\TestCase;
use Rr\Bundle\Workers\Temporal\Services\Activities\MessengerActivity;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\NoHandlerForMessageException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

final class MessengerActivityTest extends TestCase
{
    public function testDispatchDenormalizesPayloadIntoTheCommandAndReturnsHandlerResult(): void
    {
        $handled = null;

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(function (Envelope $envelope) use (&$handled): Envelope {
                // ReceivedStamp — чтобы temporal-транспорт не отправил команду в Temporal повторно
                self::assertNotNull($envelope->last(ReceivedStamp::class));
                $handled = $envelope->getMessage();

                return $envelope->with(new HandledStamp('sent', 'handler'));
            });

        $result = $this->activity($bus)->dispatch(SendEmail::class, ['id' => 42]);

        self::assertInstanceOf(SendEmail::class, $handled);
        self::assertSame(42, $handled->id);
        self::assertSame('sent', $result);
    }

    public function testDispatchFailsWhenNobodyHandlesTheCommand(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->method('dispatch')->willReturnCallback(fn(object $c) => new Envelope($c));

        $this->expectException(\LogicException::class);

        $this->activity($bus)->dispatch(SendEmail::class, ['id' => 42]);
    }

    public function testDispatchRethrowsBusFailures(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->method('dispatch')->willThrowException(new NoHandlerForMessageException('nope'));

        $this->expectException(NoHandlerForMessageException::class);

        $this->activity($bus)->dispatch(SendEmail::class, ['id' => 42]);
    }

    /**
     * @param MessageBusInterface $bus
     * @return MessengerActivity
     */
    private function activity(MessageBusInterface $bus): MessengerActivity
    {
        $serializer = new Serializer([new ObjectNormalizer()]);

        return new MessengerActivity($serializer, $serializer, $bus);
    }
}