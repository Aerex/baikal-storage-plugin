<?php

namespace Aerex\BaikalStorage\Configs;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;

class ConfigBuilder implements ConfigurationInterface {
  private $configs = [];
  private $configFile;
  private $processor;

  public function __construct($configFile) {
    $this->configFile = $configFile;
    $this->processor = new Processor();
  }

  public function add($config) {
    $this->configs[] = $config;
  }

  public function getConfigTreeBuilder() {
    $treeBuilder = new TreeBuilder('configs', 'array');
    $rootNode = $treeBuilder->getRootNode();
    $ref =  $rootNode->children()
        ->arrayNode('general')
              ->children()
                ->arrayNode('logger')
                  ->canBeEnabled()
                  ->children()
                    ->scalarNode('file')->end()
                    ->scalarNode('level')
                      ->defaultValue('ERROR')
                      ->validate()
                        ->IfNotInArray(['DEBUG', 'INFO', 'NOTICE', 'WARNING', 'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'])
                        ->thenInvalid('Invalid log level %s')
                      ->end()
                    ->end()
                  ->end()
                ->end()
               ->end()
              ->end()
            ->arrayNode('storages')
                ->children();
    foreach ($this->configs as $config) {
      $ref = $ref->append($config->get());
    }
    $ref->end();
    return $treeBuilder;
  }

  public function readContent() {
    return file_get_contents($this->configFile);
  }

  public function loadYaml() {
    $contents = $this->readContent();
    $parseContents = Yaml::parse($contents);
    return $this->processor->processConfiguration($this, [$parseContents]);
  }

  public function saveConfigs($configs) {
    $yaml = Yaml::dump($configs, 3, 2);
    file_put_contents($this->configFile, $yaml);
  }
}
