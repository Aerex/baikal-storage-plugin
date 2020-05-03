<?php 

namespace Aerex\BaikalStorage\Configs;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class TaskwarriorConfig {
  public function get() {
    $treeBuilder = new TreeBuilder('taskwarrior');
    $node = $treeBuilder->getRootNode();
    $node->children()
            ->scalarNode('data_dir')
            ->defaultValue('~/.task')
        ->end();
    return $node;
  }
}
