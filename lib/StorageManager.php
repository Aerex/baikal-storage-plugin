<?php

namespace Aerex\BaikalStorage;

use Sabre\VObject\Component\VCalendar as Calendar;

class StorageManager {

  /**
   * @var Storage[]
   */

  private $storages = []; 
    

  /**
   * @var array()
   */
  private $configs;

  public function __construct($configs){
    $this->configs = $configs; 
  }

  public function getStorages() {
    return $this->storages;
  } 

  public function getConfigs() {
    return $this->configs;
  }

  public function addStorage($name, $storage) {
   $this->storages[$name] = $storage; 
  }

  public function import(Calendar $calendar) {
    if (!isset($this->configs)) {
      throw new \Exception('StorageManger was not initialize or configs are not defined'); 
    }
    foreach ($this->configs['storages'] as $key => $value) {
      $storage = $this->storages[$key];
      if (!isset($storage)){
        throw new \Exception();
      }
      $storage->save($calendar);
    }
  }

  public function remove($uid) {
    if (!isset($this->configs)) {
      throw new \Exception('StorageManger was not initialize or configs are not defined'); 
    }
    foreach ($this->configs['storages'] as $key => $value) {
      $storage = $this->storages[$key];
      if (!isset($storage)){
        throw new \Exception();
      }
      $storage->remove($uid);
    }
  }
}
