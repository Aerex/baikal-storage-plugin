<?php

namespace Aerex\BaikalStorage\Storages;

use Sabre\VObject\Component\VCalendar as Calendar;
use Carbon\Carbon;
use Carbon\CarbonTimeZone;

class Taskwarrior implements IStorage {

  public const NAME = 'taskwarrior';
  private $tasks = [];
  private $configs;
  private $logger;
  private $tz;

  public function __construct($console, $configs, $logger) {
    $this->console = $console;
    $this->configs = $configs['storages']['taskwarrior'];
    $this->logger = $logger; 
    $this->tz = new CarbonTimeZone($configs['general']['timezone']);
  }

  public function getConfig() {
    return $this->config;
  }

  public function refresh() {
    $output = $this->console->execute('task', ['sync'], null, 
      ['TASKRC' => $this->configs['taskrc'],'TASKDATA' => $this->configs['taskdata']]);
      $this->tasks = json_decode($this->console->execute('task', ['export'], null,
        ['TASKRC' => $this->configs['taskrc'], 'TASKDATA' => $this->configs['taskdata']]), true);
      foreach ($this->tasks as $task) {
        if (isset($task['uid'])) {
          $this->tasks[$task['uid']] = $task;
        }
      }
    $this->logger->info($output);
  }

  public function vObjectToTask($vtodo) {
    if (isset($this->tasks[(string)$vtodo->UID])) {
      $task = $this->tasks[(string)$vtodo->UID];
    } else {
      $task = [];
      $task['uid'] = (string)$vtodo->UID;
    }

    if (isset($vtodo->SUMMARY)){
      $task['description'] = (string)$vtodo->SUMMARY;
    } else if(isset($vtodo->DESCRIPTION)) {
      $task['description'] = (string)$vtodo->DESCRIPTION;
    }

    if (isset($vtodo->DTSTAMP)){
      $task['entry'] = new Carbon($vtodo->DTSTAMP->getDateTime()->format(\DateTime::W3C), $this->tz);
    } 

    if (isset($vtodo->DTSTART)) {
      $task['start'] = new Carbon($vtodo->DTSTART->getDateTime()->format(\DateTime::W3C), $this->tz);
    }

    if (isset($vtodo->DTEND)){
      $task['end'] = new Carbon($vtodo->DTEND->getDateTime()->format(\DateTime::W3C), $this->tz);
    }

    if (isset($vtodo->{'LAST-MODIFIED'})) {
      $task['modified'] = new Carbon($vtodo->{'LAST-MODIFIED'}->getDateTime()->format(\DateTime::W3C), $this->tz);
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
      $task['due'] = new Carbon($vtodo->DUE->getDateTime()); 
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
            $task['end'] = new Carbon($vtodo->DTSTAMP->getDateTime()->format(\DateTime::W3C), $this->tz);
          }
          break;
        case 'CANCELED':
          $task['status'] = 'deleted';
          if (!isset($task['end'])) {
            $task['end'] = new Carbon($vtodo->DTSTAMP->getDateTime()->format(\DateTime::W3C), $this->tz);
          }
          break;
      }
    }

    if (isset($vtodo->CATEGORIES)) {
      $task['tags'] = [];
        foreach ($vtodo->CATEGORIES as $category) {
          if (isset($this->configs['project_tag_suffix'])) {
            $projTagSuffixRegExp = sprintf('/^%s/', $this->configs['project_tag_suffix']);
            if (preg_match($projTagSuffixRegExp, $category)) {
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
    try {
      if (!isset($c->VTODO)){
        throw new \Exception('Calendar event does not contain VTODO');
      }
      $this->logger->info(json_encode($c->jsonSerialize()));
      $this->refresh();
      $task = $this->vObjectToTask($c->VTODO);
      $this->logger->info(json_encode($task));
      $this->logger->info(
        sprintf('Executing TASKRC = %s TASKDATA = %s task import %s', $this->configs['taskrc'], $this->configs['taskdata'], json_encode($task))
      );
      $output = $this->console->execute('task', ['import'], $task, 
        ['TASKRC' => $this->configs['taskrc'],'TASKDATA' => $this->configs['taskdata']]);
      $this->logger->info($output);
    } catch (\Exception $e) {
      $this->logger->error($e->getTraceAsString());
      throw $e;
    }
  }
}
