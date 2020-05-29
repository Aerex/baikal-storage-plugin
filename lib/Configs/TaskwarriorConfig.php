<?php 

namespace Aerex\BaikalStorage\Configs;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class TaskwarriorConfig {
  public function get() {
    $node = new ArrayNodeDefinition('taskwarrior');
    $node->canBeEnabled()
              ->children()
                ->scalarNode('taskdata')
                  ->defaultValue('~/.task')
                ->end()
                ->scalarNode('taskrc')
                  ->defaultValue('~/.taskrc')
                ->end()
                ->scalarNode('project_tag_suffix')
                  ->defaultValue('project_')
                ->end()
              ->end();

    return $node;
  }
}
