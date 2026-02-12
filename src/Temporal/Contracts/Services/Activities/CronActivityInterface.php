<?php

namespace Rr\Bundle\Workers\Temporal\Contracts\Services\Activities;

use phpDocumentor\Reflection\Types\ClassString;
use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;

#[ActivityInterface(prefix: 'cron.')]
interface CronActivityInterface
{
    #[ActivityMethod(name: 'execute')]
    public function execute(ClassString $class, object $payload): void;
}