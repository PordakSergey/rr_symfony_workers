<?php

namespace Rr\Bundle\Workers\Tests\Temporal;

use PHPUnit\Framework\TestCase;
use Rr\Bundle\Workers\Temporal\Services\Activities\MessengerActivityOptions;

final class MessengerActivityOptionsTest extends TestCase
{
    protected function tearDown(): void
    {
        MessengerActivityOptions::configure([]);
    }

    public function testDefaultsWhenNothingIsConfigured(): void
    {
        $options = MessengerActivityOptions::make();

        self::assertSame(180, (int)$options->startToCloseTimeout->totalSeconds);
        self::assertSame(2, $options->retryOptions->maximumAttempts);
        self::assertSame(3, (int)$options->retryOptions->initialInterval->totalSeconds);
    }

    public function testConfiguredValuesAreUsed(): void
    {
        MessengerActivityOptions::configure([
            'start_to_close_timeout' => 30,
            'maximum_attempts' => 5,
            'initial_interval' => 1,
        ]);

        $options = MessengerActivityOptions::make();

        self::assertSame(30, (int)$options->startToCloseTimeout->totalSeconds);
        self::assertSame(5, $options->retryOptions->maximumAttempts);
        self::assertSame(1, (int)$options->retryOptions->initialInterval->totalSeconds);
    }

    public function testMissingKeysFallBackToDefaults(): void
    {
        MessengerActivityOptions::configure(['maximum_attempts' => 7]);

        $options = MessengerActivityOptions::make();

        self::assertSame(7, $options->retryOptions->maximumAttempts);
        self::assertSame(
            MessengerActivityOptions::START_TO_CLOSE_TIMEOUT,
            (int)$options->startToCloseTimeout->totalSeconds,
        );
    }
}