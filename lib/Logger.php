<?php

namespace Aerex\BaikalStorage;
use Monolog\Logger as Monolog;
use Monolog\Handler\StreamHandler;

class Logger {

  private $configs = ['enabled' => false];

  function __construct($configs, $tag) {
    if (isset($configs['logger'])) {
      $this->configs = $configs['logger'];
    }
    if ($this->configs['enabled']) {
      $this->logger = new Monolog($tag);
      $logLevel = Monolog::getLevels()[$this->configs['level']];
      $this->logger->pushHandler(new StreamHandler($this->configs['file'], $logLevel));
    }
  }

  public function debug($message) {
    if ($this->configs['enabled']) {
      $this->logger->debug($message);
    }
  } 

  public function info($message) {
    if ($this->configs['enabled']) {
      $this->logger->info($message);
    }
  } 

  public function notice($message) {
    if ($this->configs['enabled']) {
      $this->logger->notice($message);
    }
  } 

  public function error($message) {
    if ($this->configs['enabled']) {
      $this->logger->error($message);
    }
  } 

  public function critical($message) {
    if ($this->configs['enabled']) {
      $this->logger->critical($message);
    }
  } 
  public function alert($message) {
    if ($this->configs['enabled']) {
      $this->logger->alert($message);
    }
  } 

  public function emergency($message) {
    if ($this->configs['enabled']) {
      $this->logger->emergency($message);
    }
  } 

}
