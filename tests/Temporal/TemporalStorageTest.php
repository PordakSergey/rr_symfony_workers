<?php

namespace Rr\Bundle\Workers\Tests\Temporal;

use PHPUnit\Framework\TestCase;
use Rr\Bundle\Workers\Temporal\Services\Storage\TemporalStorage;

final class TemporalStorageTest extends TestCase
{
    public function testTaggedIteratorsAreFlattenedIntoLists(): void
    {
        $activity = new \stdClass();
        $workflow = new \stdClass();

        // tagged_iterator отдаёт RewindableGenerator, а не массив
        $storage = new TemporalStorage(
            (function () use ($activity) { yield 'a' => $activity; })(),
            (function () use ($workflow) { yield 'w' => $workflow; })(),
        );

        self::assertSame([$activity], $storage->getActivities());
        self::assertSame([$workflow], $storage->getWorkflows());
    }

    public function testEmptyByDefault(): void
    {
        $storage = new TemporalStorage([], []);

        self::assertSame([], $storage->getActivities());
        self::assertSame([], $storage->getWorkflows());
    }
}