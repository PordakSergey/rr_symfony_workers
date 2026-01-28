<?php

namespace Rr\Bundle\Workers\Runtime;

use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Runtime\RunnerInterface;
use Symfony\Component\Runtime\SymfonyRuntime;
class Runtime extends SymfonyRuntime
{
    public function getRunner(?object $application) : RunnerInterface
    {
        if ($application instanceof KernelInterface && false !== getenv('RR_MODE')) {
            $runner = new Runner($application);
            $runner->setMode(getenv('RR_MODE'));
            return $runner;
        }

        return parent::getRunner($application);
    }
}