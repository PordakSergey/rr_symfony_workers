<?php

namespace Rr\Bundle\Workers\Middlewares;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Rr\Bundle\Workers\Contracts\Middlewares\MiddlewareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\VarExporter\LazyObjectInterface;

class DoctrineORMMiddleware implements MiddlewareInterface
{
    public function __construct(
        protected ManagerRegistry    $registry,
        protected ContainerInterface $container
    )
    {

    }

    /**
     * @return void
     */
    private function preRequest(): void
    {
        $connectionServices = $this->registry->getConnectionNames();

        foreach ($connectionServices as $connectionService) {
            if (!$this->container->initialized($connectionService)) {
                continue;
            }

            $connection = $this->container->get($connectionService);
            assert($connection instanceof Connection);

            if ($connection->isConnected() && $this->ping($connection) === false) {
                $connection->close();
            }
        }
    }

    /**
     * @return void
     */
    private function postResponse(): void
    {
        $managerNames = $this->registry->getManagerNames();

        foreach ($managerNames as $managerName) {
            if (!$this->container->initialized($managerName)) {
                continue;
            }

            $manager = $this->container->get($managerName);
            \assert($manager instanceof EntityManagerInterface);
            if ($manager instanceof LazyObjectInterface) {
                continue;
            }

            if (class_exists(LazyObjectInterface::class) && $manager instanceof LazyObjectInterface) {
                continue;
            }

            if (!$manager->isOpen()) {
                /*$this->eventDispatcher->dispatch(new ForceKernelRebootEvent(
                    "entity manager '$managerName' is closed and the package `symfony/proxy-manager-bridge` is not installed so kernel reset will not re-open it"
                ));*/

                return;
            }
        }
    }

    /**
     * @param Connection $connection
     * @return bool
     */
    private function ping(Connection $connection): bool
    {
        try {
            $connection->executeQuery($connection->getDatabasePlatform()->getDummySelectSQL());
            return true;
        } catch (\Exception | \Doctrine\DBAL\Exception) {
            return false;
        }
    }

    /**
     * @param Request $request
     * @param HttpKernelInterface $next
     * @return \Iterator
     * @throws \Exception
     */
    public function process(Request $request, HttpKernelInterface $next): \Iterator
    {
        $this->preRequest();

        yield $next->handle($request);

        $this->postResponse();
    }
}