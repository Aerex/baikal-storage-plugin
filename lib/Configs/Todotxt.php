<?php 

namespace Aerex\BaikalStorage\Configs;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Config extends AbstractConfig {
  protected function getConfigTree() {
    $treeBuilder = new TreeBuilder('config');
    $rootNode = $treeBuilder->getRootNode();

    $rootNode->children
             ->scalarNode('storage')
              ->isRequired()
              ->ifNotInArray(['todotxt'])
              ->thenInvalid('Invalid storage %s')
             ->end();
    return $treeBuilder;
  }
}

