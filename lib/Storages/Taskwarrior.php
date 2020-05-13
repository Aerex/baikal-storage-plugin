<?php

namespace Aerex\BaikalStorage\Storages;

use Sabre\VObject\Component\VCalendar as Calendar;
use Carbon\Carbon;

class Taskwarrior implements IStorage {

  private const DATA_FILES = ['pending.data', 'completed.data', 'undo.data'];
  public const NAME = 'taskwarrior';
  private $tasks = [];
  private $configDir;
  private $configs;
  public function __construct($console, $configDir, $configs) {
    $this->console = $console;
    $this->configDir = $configDir;
    $this->configs = $configs['taskwarrior'];
  }

  public function getConfig() {
    return $this->config;
  }

  public function refresh() {
    $fp = fopen(sprintf('%s/taskwarrior-baikal-storage.lock', $this->configDir), 'a'); 

    if (!$fp || !flock($fp, LOCK_EX | LOCK_NB, $eWouldBlock) || $eWouldBlock) {
      fputs(STDERR, 'Could not get lock');
    }

    $mtime = 0;
    $tasksUpdated = false;
    foreach (Taskwarrior::DATA_FILES as $dataFile) {
      $fmtime = filemtime(sprintf('%s/%s', $this->configs['taskdata'], $dataFile));
      if ($fmtime > $mtime) {
        $mtime = $fmtime;
        $tasksUpdated = true;
      }
    }

    if ($tasksUpdated) {
      $tasks = json_decode($this->console->execute('task', ['export'], null, 
        ['TASKRC' => $this->configs['taskrc'], 'TASKDATA' => $this->configs['taskdata']]), true);
      foreach ($tasks as $task) {
        $this->tasks[$task['uuid']] = $task;
      }
    }
    fclose($fp);
    unlink(sprintf('%s/taskwarrior-baikal-storage.lock', $this->configDir));
  }

  public function vObjectToTask($vtodo) {
    if (isset($this->tasks['uid']) && $this->tasks['uid'] == $vtodo->UID) {
      $task = $this->tasks['uid'];
    } else {
      $task = [];
      $task['uid'] = (string)$vtodo->UID;
    }


    if (!isset($vtodo->DESCRIPTION) && isset($vtodo->SUMMARY)){
      $task['description'] = (string)$vtodo->SUMMARY;
    } else {
      $task['description'] = (string)$vtodo->DESCRIPTION;
    }

    if (isset($vtodo->DTSTAMP)){
      $task['entry'] = new Carbon($vtodo->DTSTAMP->getDateTime()->format(\DateTime::W3C));
    } 

    if (isset($vtodo->DTSTART)) {
      $task['start'] = new Carbon($vtodo->DTSTART->getDateTime()->format(\DateTime::W3C));
    }

    if (isset($vtodo->DTEND)){
      $task['end'] = new Carbon($vtodo->DTEND->getDateTime()->format(\DateTime::W3C));
    }

    if (isset($vtodo->{'LAST-MODIFIED'})) {
      $task['modified'] = new Carbon($vtodo->{'LAST-MODIFIED'}->getDateTime()->format(\DateTime::W3C));
    }

    if (isset($vtodo->PRIORITY)) {
      $priority = $vtodo->PRIORITY->getJsonValue();
      if ($priority < 5) {
        $task['priority'] = 'H';
      } else if ($priority === 5) {
        $task['priority'] = 'M';
      } else if ($priority > 5 && $priority < 10) {
        $task['priority'] = 'L';
      }
    }

    if (isset($vtodo->DUE)){
      $task['due'] = new Carbon($vtodo->DUE->getDateTime()->format(\DateTime::W3C)); 
    }

    if (isset($vtodo->RRULE)) {
      $rules = $vtodo->RRULE->getParts();
      if (isset($rules['FREQ'])) {
        $task['recu'] = $rules['FREQ'];
      }
      if (isset($rules['UNTIL'])) {
        $task['until'] = $rules['UNTIL'];
      }
    }

    if (isset($vtodo->STATUS)) {
      switch((string)$vtodo->STATUS) {
      case 'NEEDS-ACTION':
        $task['status'] = 'pending';
        break;
      case 'COMPLETED':
        $task['status'] = 'completed';
        if (!isset($task['end'])) {
          $task['end'] = new Carbon($vtodo->DTSTAMP->getDateTime()->format(\DateTime::W3C));
        }
        break;
      case 'CANCELED':
        $task['status'] = 'deleted';
        if (!isset($task['end'])) {
          $task['end'] = new Carbon($vtodo->DTSTAMP->getDateTime()->format(\DateTime::W3C));
        }
        break;
      }

    }

    if (isset($vtodo->CATEGORIES)) {
      $task['tags'] = [];
      foreach ($vtodo->CATEGORIES as $category) {
        if (isset($this->configs['project_tag_suffix'])) {
          $projTagSuffixRegExp = sprintf('/^%s_/', $this->configs['project_tag_suffix']);
          if (preg_match($category, $projTagSuffixRegExp)) {
            $task['project'] = preg_replace($projTagSuffixRegExp, '', $category);
            continue;
          }
        }
        $task['tags'] = $category;
      }
    }

    return $task;
      }

      public function save(Calendar $c) {
        if (!isset($c->VTODO)){
          throw new \Exception('Calendar event does not contain VTODO');
        }
        $this->refresh();
        $task = $this->vObjectToTask($c->VTODO);
        $this->console->execute('task', ['import'], $task, 
          ['TASKRC' => $this->configs['taskrc'],'TASKDATA' => $this->configs['taskdata']]);
      } 
    }
