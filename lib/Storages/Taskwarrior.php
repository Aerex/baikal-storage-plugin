<?php

namespace Aerex\BaikalStorage\Storages;

use Sabre\VObject\Component\VCalendar as Calendar;

class Taskwarrior implements IStorage {

  public const NAME = 'taskwarrior';
  private $tasks = [];
  private $configs;
  private $logger;

  public function __construct($console, $configs, $logger) {
    $this->console = $console;
    $this->configs = $configs['storages']['taskwarrior'];
    $this->logger = $logger; 
  }

  public function getConfigBrowser() {
    $html  = '<tr>';
    $html .= '<th>taskrc</th>';
    $html .= '<td>The enivronment variable overrides the default and the command line specification of the .taskrc file</td>';
    $html .= '<td><input name="tw_taskrc" type="text" value="' . $this->configs['taskrc'] . '"></td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<th>taskdata</th>';
    $html .= '<td>The environment variable overrides the default and the command line, and the "data.location" configuration setting of the task data directory</td>';
    $html .= '<td><input name="tw_taskdata" type="text" value="' . $this->configs['taskdata'] . '"></td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<th>default_calendar</th>';
    $html .= '<td>The default calendar to send tasks if no task project is set. The value is the calendar\'s displayname</td>';
    $html .= '<td><input name="tw_default_calendar" type="text" value="' . $this->configs['default_calendar'] . '"></td>';
    $html .= '</tr>';
    return $html;
  }

  public function updateConfigs($postData) {
    if (isset($postData['tw_taskrc'])) {
      $this->configs['taskrc'] = $postData['tw_taskrc'];
    }

    if (isset($postData['tw_taskdata'])){
      $this->configs['taskdata'] = $postData['tw_taskdata'];
    }
    if (isset($postData['tw_default_calendar'])){
      $this->configs['default_calendar'] = $postData['tw_default_calendar'];
    }

    return $this->configs;

  }

  public function refresh() {
    $this->logger->info('Syncing taskwarrior tasks...');
    $this->console->execute('task', ['sync'], null, 
      ['TASKRC' => $this->configs['taskrc'],'TASKDATA' => $this->configs['taskdata']]);
      $this->tasks = json_decode($this->console->execute('task', ['export'], null,
        ['TASKRC' => $this->configs['taskrc'], 'TASKDATA' => $this->configs['taskdata']]), true);
      foreach ($this->tasks as $task) {
        if (isset($task['uid'])) {
          $this->tasks[$task['uid']] = $task;
        }
      }
  }

  public function vObjectToTask($vtodo, string $displayname) {
    if (isset($this->tasks[(string)$vtodo->UID])) {
      $task = $this->tasks[(string)$vtodo->UID];
    } else {
      $task = [];
      $task['uid'] = (string)$vtodo->UID;
    }

    if (isset($vtodo->SUMMARY)){
      $task['description'] = (string)$vtodo->SUMMARY;
    } 

    if (isset($vtodo->DESCRIPTION)) {
      $annotations = [];
      if (isset($task['annotations'])) {
        $annotations  = $task['annotations'];
      }
      $task['annotations'] = [];
      $descriptionLines = explode('\n', $vtodo->DESCRIPTION);
      foreach ($descriptionLines as $key => $descriptionLine) {
          $annotationEntry = $vtodo->DTSTAMP->getDateTime()->modify("+$key second")->format(\DateTime::ISO8601);
          foreach ($annotations as $annotation) {
            if ($annotation['description'] == $descriptionLine) {
              $annotationEntry = $annotation['entry'];
              break;
            }
          }
          array_push($annotations, ['description' => $descriptionLine, 'entry' => $annotationEntry]);
          $task['annotations'] = $annotations;
      }
    }
    if (!isset($task['entry'])){
      $task['entry'] = $vtodo->DTSTAMP->getDateTime()->format(\DateTime::ISO8601);
    } 

    if (isset($vtodo->DTSTART)) {
      $task['start'] = $vtodo->DTSTART->getDateTime()->format(\DateTime::ISO8601);
    }

    if (isset($vtodo->DTEND)){
      $task['end'] = $vtodo->DTEND->getDateTime()->format(\DateTime::ISO8601);
    }

    if (isset($vtodo->{'LAST-MODIFIED'})) {
      $task['modified'] = $vtodo->{'LAST-MODIFIED'}->getDateTime()->format(\DateTime::ISO8601);
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
      $task['due'] = $vtodo->DUE->getDateTime()->format(\DateTime::ISO8601);
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
            $task['end'] = $vtodo->DTSTAMP->getDateTime()->format(\DateTime::ISO8601);
          }
          break;
        case 'CANCELED':
          $task['status'] = 'deleted';
          if (!isset($task['end'])) {
            $task['end'] = $vtodo->DTSTAMP->getDateTime()->format(\DateTime::ISO8601);
          }
          break;
      }
    }

    if (isset($vtodo->CATEGORIES)) {
      $task['tags'] = $vtodo->CATEGORIES->getJsonValue();
      }

    if (isset($vtodo->GEO)){
      $task['geo'] = $vtodo->GEO->getRawMimeDirValue();
    }

    if ($this->configs['default_calendar'] != $displayname) {
      $task['project'] = $displayname;
    }

      return $task;
  }

  public function save(Calendar $c, string $displayname) {
    try {
      if (!isset($c->VTODO)){
        throw new \Exception('Calendar event does not contain VTODO');
      }
      $this->logger->info(json_encode($c->jsonSerialize()));
      $this->refresh();
      $task = $this->vObjectToTask($c->VTODO, $displayname);
      $this->logger->info(json_encode($task));
      $this->logger->info(
        sprintf('Executing TASKRC = %s TASKDATA = %s task import %s', $this->configs['taskrc'], $this->configs['taskdata'], json_encode($task))
      );
      $output = $this->console->execute('task', ['import'], $task, 
        ['TASKRC' => $this->configs['taskrc'],'TASKDATA' => $this->configs['taskdata']]);
      $this->refresh();
      $this->logger->info($output);
    } catch (\Exception $e) {
      $this->logger->error($e->getTraceAsString());
      throw $e;
    }
  }

  public function remove($uid) {
    try {
      $this->logger->info(sprintf('Deleting iCal %s from taskwarrior', $uid));
      $this->refresh();
      if (!array_key_exists((string)$uid, $this->tasks)) {
        $this->logger->warn(sprintf('Could not find task %s to be remove. Skipping', (string)$uid)); 
        return;
      }
      $task = $this->tasks[(string)$uid];
      if (isset($task) && $task['status'] !== 'deleted') {
        $uuid = $task['uuid'];
        $this->logger->info(
          sprintf('Executing TASKRC = %s TASKDATA = %s task delete %s', $this->configs['taskrc'], $this->configs['taskdata'], $uuid) 
        );
        $output = $this->console->execute('task', ['delete', (string)$uuid], null, 
          ['TASKRC' => $this->configs['taskrc'],'TASKDATA' => $this->configs['taskdata']]);
        $this->logger->info($output);
        $this->refresh();
      } else if (isset($task) && $task['status'] === 'deleted') {
        $this->logger->warn(sprintf('Task %s has already been deleted', $task['uuid']));
      } else {
        $this->logger->error(sprintf('Could not find task for iCal %s to be deleted', $uid));
      }

    } catch (\Exception $e) {
      $this->logger->error($e->getMessage());
      $this->logger->error($e->getTraceAsString());
      throw $e;
    }

  }
}
