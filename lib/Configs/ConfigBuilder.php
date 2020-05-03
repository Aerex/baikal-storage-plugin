<?php 

namespace Aerex\BaikalStorage\Configs;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;

class ConfigBuilder implements ConfigurationInterface {
  private $configs = [];
  private $configFile;

  public function __construct($configFile = '~/.config/baikal') {
    $this->configFile = $configFile;
    $this->processor = new Processor();
  }

  public function add($config) {
    $this->configs[] = $config;
  }

  public function getConfigTreeBuilder() {
    $treeBuilder = new TreeBuilder('configs');
    $rootNode = $treeBuilder->getRootNode();
    $ref = $rootNode->children();
    foreach ($this->configs as $config) {
      $ref = $ref->append($config->get());
    }
    $ref->end();
    return $treeBuilder;
  }

  public function readContent() {
    if (!is_dir($this->configFile)) {
      mkdir($this->configFile);
    }
    $contents = sprintf('%s/storage.yml', $this->configFile);
    return file_get_contents($contents);
  }

  public function loadYaml() {
    $contents = $this->readContent();
    $parseContents = Yaml::parse($contents);
    return $this->processor->processConfiguration($this, [$parseContents]);
  }
}
