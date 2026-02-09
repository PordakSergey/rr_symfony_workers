<?php

namespace Rr\Bundle\Workers\Temporal;

use Temporal\Activity\ActivityInterface;

#[ActivityInterface]
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