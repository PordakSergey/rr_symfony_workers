<?php

namespace Rr\Bundle\Workers\EventListeners;

use Rr\Bundle\Workers\Event\KernelRebootEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: KernelRebootEvent::class, method: 'handle')]
class OnKernelRebootEventListener
{
    public function handle(KernelRebootEvent $event): void
    {

    }
}