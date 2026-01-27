<?php

namespace Rr\Bundle\Workers\Runtime;

use Rr\Bundle\Workers\Contracts\Storage\WorkerStorageInterface;
use Spiral\RoadRunner\Environment\Mode;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Runtime\RunnerInterface;

class Runner implements RunnerInterface
{
    /**
     * @param KernelInterface $kernel
     * @param string $mode
     */
    public function __construct(
        private readonly KernelInterface $kernel,
        private string                   $mode)
    {
    }

    /**
     * @return int
     */
    public function run(): int
    {
        $_SERVER['APP_RUNTIME_MODE'] = \sprintf('web=%d&worker=1', $this->mode === Mode::MODE_HTTP ? 1 : 0);

        $this->kernel->boot();
        $registry = $this->kernel->getContainer()->get(WorkerStorageInterface::class);
        $worker = $registry->getWorker($this->mode);

        if ($worker === null) {
            error_log(\sprintf('Missing RR worker implementation for %s mode', $this->mode));
            return 1;
        }

        $worker->run();

        return 0;
    }
}