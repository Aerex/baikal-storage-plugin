<?php 

namespace Aerex\BaikalStorage\Configs;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;

class ConfigBuilder implements ConfigurationInterface {
  private $configs = [];
  private $configDir;

  public function __construct($configDir = null) {
    if (!isset($configDir)) {
      $this->configDir = $this->getHomeDir() .  '~/.config/baikal';
    } else {
      $this->configDir = $configDir;
    }
    $this->processor = new Processor();
  }

  private function getHomeDir() {
    if (stristr(PHP_OS, 'WIN')) {
      return rtrim($_SERVER['HOMEDRIVE'] . $_SERVER['HOMEPATH'], '\\/');
    } else {
      return rtrim($_SERVER['HOME'], '/');
    }

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
    if (!is_dir($this->configDir)) {
      mkdir($this->configDir, 0755, true);
    }
    $contents = sprintf('%s/storage.yml', $this->configDir);
    return file_get_contents($contents);
  }

  public function loadYaml() {
    $contents = $this->readContent();
    $parseContents = Yaml::parse($contents);
    return $this->processor->processConfiguration($this, [$parseContents]);
  }
}
