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
                ->arrayNode("workers")
                    ->addDefaultsIfNotSet()
                    ->info('Roadrunner workers')
                    ->children()
                        ->arrayNode('http')->children()
                            ->booleanNode("enabled")->info('Enable http worker')->defaultTrue()->end()
                        ->arrayNode('grpc')->children()
                            ->booleanNode("enabled")->info('Enable Grpc worker')->defaultTrue()->end()
        ;

        return $builder;
    }
}