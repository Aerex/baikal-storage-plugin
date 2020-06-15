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
                  ->info('The environment variable overrides the default and the command line, and the "data.location" configuration setting of the task data directory')
                ->end()
                ->scalarNode('taskrc')
                  ->defaultValue('~/.taskrc')
                  ->info('The enivronment variable overrides the default and the command line specification of the .taskrc file')
                ->end()
                ->scalarNode('project_category_prefix')
                  ->defaultValue('project_')
                  ->info('The word after the given prefix for a iCal category will be used to identify a task\'s project')
                ->end()
              ->end();

    return $node;
  }
}
