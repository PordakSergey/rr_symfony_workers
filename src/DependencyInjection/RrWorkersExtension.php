<?php

namespace Rr\Bundle\Workers\DependencyInjection;


use Doctrine\Persistence\ManagerRegistry;
use Rr\Bundle\Workers\Cache\KvCacheAdapter;
use Rr\Bundle\Workers\Middlewares\DoctrineORMMiddleware;
use Spiral\Goridge\RPC\RPC;
use Spiral\Goridge\RPC\RPCInterface;
use Spiral\RoadRunner\GRPC\ServiceInterface;
use Spiral\RoadRunner\KeyValue\Factory;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;

class RrWorkersExtension extends Extension
{

    /**
     * @param array $configs
     * @param ContainerBuilder $container
     * @return void
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . "/../../config"));
        $loader->load("services.php");

        if (!empty($config['kv']['storages'])) {
            $this->configureKv($config, $container);
        }

        $container
            ->register(DoctrineORMMiddleware::class)
            ->addArgument(new Reference(ManagerRegistry::class))
            ->addArgument(new Reference('service_container'));

        if (interface_exists(ServiceInterface::class)) {
            $container->registerForAutoconfiguration(ServiceInterface::class)
                ->addTag('roadrunner.grpc_service');
        }
    }

    /**
     * @param array $config
     * @param ContainerBuilder $container
     * @return void
     */
    public function configureKv(array $config, ContainerBuilder $container): void
    {
        if (!class_exists(Factory::class)) {
            throw new LogicException('RoadRunner KV support cannot be enabled as spiral/roadrunner-kv is not installed. Try running "composer require spiral/roadrunner-kv".');
        }

        if (!class_exists(RPC::class)) {
            throw new LogicException('RoadRunner KV support cannot be enabled as spiral/goridge is not installed. Try running "composer require spiral/goridge".');
        }

        if (!interface_exists(AdapterInterface::class)) {
            throw new LogicException('RoadRunner KV support cannot be enabled as symfony/cache is not installed. Try running "composer require symfony/cache".');
        }

        $storages = $config['kv']['storages'];

        foreach ($storages as $storage) {
            $container->register('cache.adapter.roadrunner.kv_'.$storage, KvCacheAdapter::class)
                ->setFactory([KvCacheAdapter::class, 'createConnection'])
                ->setArguments(['', [ // Symfony overrides the first argument with the DSN, so we pass an empty string
                    'rpc' => $container->getDefinition(RPCInterface::class),
                    'storage' => $storage,
                ]]);
        }
    }
}