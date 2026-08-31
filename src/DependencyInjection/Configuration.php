<?php

namespace Rr\Bundle\Workers\DependencyInjection;

use Rr\Bundle\Workers\Workers\TemporalWorker;
use Spiral\RoadRunner\Jobs\OptionsInterface;
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
        $builder = new TreeBuilder("rr_workers");

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
                ->arrayNode("jobs")
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('default_queue')
                            ->info('RoadRunner pipeline used by RrJobDispatcher when no queue is given.')
                            ->defaultValue('default')
                        ->end()
                        ->arrayNode('queues')
                            ->info('Pipeline name (из .rr.yaml) => default task options. Опции push() их перекрывают.')
                            ->useAttributeAsKey('queue')
                            ->defaultValue(['default' => []])
                            ->arrayPrototype()
                                ->children()
                                    ->integerNode('delay')->defaultValue(OptionsInterface::DEFAULT_DELAY)->end()
                                    ->integerNode('priority')->defaultValue(OptionsInterface::DEFAULT_PRIORITY)->end()
                                    ->booleanNode('auto_ack')->defaultValue(OptionsInterface::DEFAULT_AUTO_ACK)->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode("temporal")
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('default_queue')
                            ->info('Task queue used when dispatching jobs and cron jobs.')
                            ->defaultValue(TemporalWorker::DEFAULT_TASK_QUEUE)
                        ->end()
                        ->arrayNode('workers')
                            ->info('Task queue name => worker options. Empty activities/workflows means "register everything tagged".')
                            ->useAttributeAsKey('queue')
                            ->defaultValue([TemporalWorker::DEFAULT_TASK_QUEUE => []])
                            ->arrayPrototype()
                                ->children()
                                    ->integerNode('max_concurrent_activities')->defaultValue(100)->end()
                                    ->integerNode('max_concurrent_workflows')->defaultValue(100)->end()
                                    ->integerNode('activity_pollers')->defaultValue(10)->end()
                                    ->integerNode('workflow_pollers')->defaultValue(5)->end()
                                    ->arrayNode('activities')->defaultValue([])->scalarPrototype()->end()->end()
                                    ->arrayNode('workflows')->defaultValue([])->scalarPrototype()->end()->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
        ;

        return $builder;
    }
}