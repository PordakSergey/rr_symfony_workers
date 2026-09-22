<?php

namespace Rr\Bundle\Workers\Temporal\Services\Activities;

use Carbon\CarbonInterval;
use Symfony\Component\DependencyInjection\Attribute\Exclude;
use Temporal\Activity\ActivityOptions;
use Temporal\Common\RetryOptions;

/**
 * Опции messenger-activity из rr_workers.temporal.activity.
 *
 * Состояние статическое, потому что workflow инстанцирует сам Temporal — ни контейнера,
 * ни конструктора там нет. Значения проставляет TemporalWorker::run() перед запуском фабрики.
 */
#[Exclude]
final class MessengerActivityOptions
{
    public const int START_TO_CLOSE_TIMEOUT = 180;
    public const int MAXIMUM_ATTEMPTS = 2;
    public const int INITIAL_INTERVAL = 3;

    /** @var array<string, int> */
    private static array $config = [];

    /**
     * @param array<string, int> $config From rr_workers.temporal.activity
     * @return void
     */
    public static function configure(array $config): void
    {
        self::$config = $config;
    }

    /**
     * @return ActivityOptions
     */
    public static function make(): ActivityOptions
    {
        return ActivityOptions::new()
            ->withStartToCloseTimeout(CarbonInterval::seconds(
                self::$config['start_to_close_timeout'] ?? self::START_TO_CLOSE_TIMEOUT
            ))
            ->withRetryOptions(
                RetryOptions::new()
                    ->withMaximumAttempts(self::$config['maximum_attempts'] ?? self::MAXIMUM_ATTEMPTS)
                    ->withInitialInterval(CarbonInterval::seconds(
                        self::$config['initial_interval'] ?? self::INITIAL_INTERVAL
                    ))
            );
    }
}