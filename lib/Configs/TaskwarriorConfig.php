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
                ->scalarNode('default_calendar')
                  ->info('The default calendar to send tasks if no task project is set. The value is the calendar\'s displayname')
                ->end()
              ->end();

    return $node;
  }
}

