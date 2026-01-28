<?php

namespace Rr\Bundle\Workers\EventListener;

use Rr\Bundle\Workers\Event\WorkerStartEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: WorkerStartEvent::class, method: 'handle')]
class DoctrineOrmListener
{
    public function __construct()
    {

    }

    public function handle(WorkerStartEvent $event): void
    {

    }
}