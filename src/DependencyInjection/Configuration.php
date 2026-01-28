<?php

namespace Rr\Bundle\Workers\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{

    /**
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder("rr_bundle");

        /** @var ArrayNodeDefinition $root */
        $root = $builder->getRootNode();

        $root
            ->info('https://github.com/PordakSergey/rr_symfony_workers')
            ->children()
                ->arrayNode("kv")
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('storages')
                            ->defaultValue([])
                            ->scalarPrototype()->end()
                        ->end()
                    ->end()
                ->end()
        ;

        return $builder;
    }
}