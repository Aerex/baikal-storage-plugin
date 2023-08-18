<?php

namespace Aerex\BaikalStorage;
use Monolog\Logger as Monolog;
use Monolog\Handler\StreamHandler;

class Logger {

  private $configs = ['enabled' => false];
  private $logger;

  function __construct($configs, $tag) {
    if (isset($configs['general']) && isset($configs['general']['logger'])) {
      $this->configs = $configs['general']['logger'];
    }
    if ($this->configs['enabled']) {
      $this->createLoggerFile();
      $this->logger = new Monolog($tag);
      $logLevel = Monolog::getLevels()[$this->configs['level']];
      $this->logger->pushHandler(new StreamHandler($this->configs['file'], $logLevel));
    }
  }

  public function createLoggerFile() {
    if (!file_exists($this->configs['file'])) {
      if (!fopen($this->configs['file'], 'w')) {
        throw new \Exception(sprintf('Could not create logger file %s', $this->configs['file']));
      }
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
  public function warn($message) {
    if ($this->configs['enabled']) {
      $this->logger->warning($message);
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
