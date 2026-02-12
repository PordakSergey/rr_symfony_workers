<?php

namespace Rr\Bundle\Workers\Temporal\Services\Activities;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Temporal\Activity\ActivityInterface;

#[ActivityInterface]
#[Autoconfigure(tags: ['temporal.activity'])]
class DemoActivity
{
    /**
     * @param string $message
     * @return string
     */
    public function print(string $message): string
    {
        return 'Aboba ' . $message;
    }
}