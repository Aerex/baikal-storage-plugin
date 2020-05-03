<?php

namespace Aerex\BaikalStorage\Storages;

use Sabre\VObject\Component\VCalendar as Calendar;
use Carbon\Carbon;

class Taskwarrior implements IStorage {

  private const DATA_FILES = ['pending.data', 'completed.data', 'undo.data'];
  private $rawConfigs;
  public const NAME = 'taskwarrior';
  private $tasks = [];
  public function __construct($console, $config) {
    $this->console = $console;
    $this->config = $config;
  }

  public function getConfig() {
    return $this->config;
  }

  public function setRawConfigs($rawConfigs) {
    $this->rawConfigs = $rawConfigs;
  }

  public function refresh() {
    $dataDir = $this->rawConfigs['data_dir'];
    $fp = fopen(sprintf('%s/taskwarrior-baikal-storage.lock', $dataDir), 'a'); 

    if (!$fp || !flock($fp, LOCK_EX | LOCK_NB, $eWouldBlock) || $eWouldBlock) {
      fputs(STDERR, 'Could not get lock');
    }

    $mtime = 0;
    $tasksUpdated = false;
    foreach (Taskwarrior::DATA_FILES as $dataFile) {
      $fmtime = filemtime(sprintf('%s/%s', $this->config['data_dir'], $dataFile));
        if ($fmtime > $mtime) {
          $mtime = $fmtime;
          $tasksUpdated = true;
        }
    }

    if ($tasksUpdated) {
      $tasks = $this->console->execute('task', ['export']);
      foreach ($tasks as $task) {
        $this->tasks[$task['uuid']] = $task;
      }
    }
    fclose($fp);
    unlink(sprintf('%s/taskwarrior-baikal-storage.lock', $dataDir));
  }

  public function vObjectToTask($vtodo) {
    if ($this->tasks['uid'] == $vtodo->UID) {
      $task = $this->tasks['uid'];
    } else {
      $task = [];
      $task['uid'] = $vtodo->UID;
    }


    if (!isset($vtodo->DESCRIPTION) && isset($vtodo->SUMMARY)){
      $task['description'] = $vtodo->SUMMARY;
    } else {
      $task['description'] = $vtodo->DESCRIPTION;
    }

    if (isset($vtodo->DTSTAMP)){
      $task['entry'] = new Carbon($vtodo->DTSTAMP->getDateTime()->format(\DateTime::W3C));
    } 

    if (isset($vtodo->DUE)){
      $task['due'] = new Carbon($vtodo->DUE->getDateTime()->format(\DateTime::W3C)); 
    }

    return $task;
  }

  public function save(Calendar $c) {
    if (!isset($c->VTODO)){
      throw new \Exception('Calendar event does not contain VTODO');
    }
    $this->refresh();
    $task = $this->vObjectToTask($c->VTODO);
    $this->console->execute('task', ['import'], $task);
  } 
}
