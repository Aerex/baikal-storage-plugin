<?php

namespace Aerex\BaikalStorage\Storages;

use Sabre\VObject\Component\VCalendar as Calendar;
use Carbon\Carbon;
class Todotxt implements IStorage {

  private $dataFiles = [];
  public const name = 'taskwarrior';
  private $todos = [];
  public function __construct($console) {
    $this->console = $console;
  }

  public function setConfig($config) {
    $this->config = $config;
    array_push($this->dataFiles, $config['todo_file']);
    array_push($this->dataFiles, $config['done_file']);
    array_push($this->dataFiles, $config['report_file']);
  }

  private function parseRaw($rawTodos) {


  }

  private function refresh() {
    $dataDir = $this->config['todo_dir'];
    $fp = fopen(sprintf('%s/baikal-todo-storage.lock', $dataDir), 'a'); 

    if (!$fp || !flock($fp, LOCK_EX | LOCK_NB, $eWouldBlock) || $eWouldBlock) {
      fputs(STDERR, 'Could not get lock');
    }

    $mtime = 0;
    $tasksUpdated = false;
    foreach ($this->dataFiles as $dataFile) {
      $fmtime = filemtime(sprintf('%s/%s', $this->config['data_dir'], $dataFile));
        if ($fmtime > $mtime) {
          $mtime = $fmtime;
          $tasksUpdated = true;
          break;
        }
    }

    if ($tasksUpdated) {
      $rawTodos = $this->console->execute('cat', [$this->config['todo_file']]);
      $todos = $this->parseRaw($rawTodos);
      foreach ($todos as $todo) {
        $this->todos[$todo['line']] = $todos;
      }
    }
    fclose($fp);
    unlink(sprintf('%s/baikal-todo-storage.lock', $dataDir));
  }

  public function vObjectToTodo($vtodo) {
    $task = [];

    $task['uid'] = $vtodo->UID;

    if(!isset($vtodo->DESCRIPTION) && isset($vtodo->SUMMARY)){
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
      throw new Exception('Could not find iCal VTODO event');
    }
    $this->refresh();
    $task = $this->vObjectToTodo($c->VTODO);
    $this->console->execute('task', ['import'], $task);
  } 
}
