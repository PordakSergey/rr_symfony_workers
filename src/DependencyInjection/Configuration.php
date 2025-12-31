<?php

namespace Rr\Bundle\Workers\DependencyInjection;

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

        $builder->getRootNode()
            ->info($this->toInfo(['https://github.com/PordakSergey/rr_symfony_workers']))
            ->children()
                ->arrayNode("workers")
        ;

        return $builder;
    }

    /**
     * @param array $lines
     * @return string
     */
    private function toInfo(array $lines): string
    {
        if(!$this->isDumpingDefaultConfiguration()) {
            return implode("\n", $lines);
        }

        $longest = 0;
        $boxLines = [];
        foreach ($lines as $line) {
            $longest = max($longest, strlen($line));
            $boxLines[] = sprintf("│ %s", $line);
        }

        $divider = str_repeat("─", $longest + 2);

        $boxLines = implode("\n", $boxLines);

        return sprintf(<<<TEXT
┌{$divider}
$boxLines
├{$divider}
│
TEXT);
    }

    /**
     * @return bool
     */
    private function isDumpingDefaultConfiguration(): bool
    {
        if(!isset($_SERVER["PHP_SELF"])) {
            return false;
        }

        // assuming people won't wrap or rename this
        if(!str_contains($_SERVER["PHP_SELF"], "console")) {
            return false;
        }

        if(!isset($_SERVER["argv"]) || !is_array($_SERVER["argv"])) {
            return false;
        }

        return in_array("config:dump-reference", $_SERVER["argv"]);
    }
}