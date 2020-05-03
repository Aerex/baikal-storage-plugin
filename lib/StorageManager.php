<?php

namespace Aerex\BaikalStorage;

use Sabre\VObject\Component\VCalendar as Calendar;
use Aerex\BaikalStorage\Configs\ConfigBuilder;

class StorageManager {

  /**
   * @var Storage[]
   */

  private $storages = []; 
    

  /**
   * @var Config
   */
  private $configBuilder;
  private $configs;

  public function __construct($configBuilder){
    $this->configBuilder = $configBuilder; 
  }

  public function getStorages() {
    return $this->storages;
  } 

  public function getConfigs() {
    return $this->configs;
  }

  public function addStorage($name, $storage) {
    $this->configBuilder->add($storage->getConfig());
   $this->storages[$name] = $storage; 
  }

  public function init() {
    $this->configs = $this->configBuilder->loadYaml();
  }

  public function import(Calendar $calendar) {
    if (!isset($this->configs)) {
      throw new \Exception('StorageManger was not initialize or configs are not defined'); 
    }
    foreach ($this->configs as $key => $value) {
      $storage = $this->storages[$key];
      if (!isset($storage)){
        throw new \Exception();
      }
      $storage->setRawConfigs($this->configs[$key]);
      $storage->save($calendar);
    }
  }
}
