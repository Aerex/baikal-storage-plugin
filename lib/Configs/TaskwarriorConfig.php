<?php 

namespace Aerex\BaikalStorage\Configs;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class TaskwarriorConfig {
  public function get() {
    $treeBuilder = new TreeBuilder();
    $node = $treeBuilder->root('taskwarrior');
    $node->children()
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
