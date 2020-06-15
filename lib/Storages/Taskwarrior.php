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
    $html .= '<th>project_category_prefix</th>';
    $html .= "<td>The word after the given prefix for a iCal category will be used to identify a task's project</td>";
    $html .= '<td><input name="tw_project_category_prefix" placeholder ="project_" name="tw_project_category_prefix" type="text" value="' . $this->configs['project_category_prefix'] . '"></td>';
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

    if (isset($postData['tw_project_category_prefix'])){
      $this->configs['project_category_prefix'] = $postData['tw_project_category_prefix'];
    }

    return $this->configs;

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
          array_push(['description' => $descriptionLine, 'entry' => $annotationEntry]);
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
      $task['tags'] = [];
        foreach ($vtodo->CATEGORIES as $category) {
          if (isset($this->configs['project_category_prefix'])) {
            $projTagSuffixRegExp = sprintf('/^%s/', $this->configs['project_category_prefix']);
            if (preg_match($projTagSuffixRegExp, $category)) {
              $task['project'] = preg_replace($projTagSuffixRegExp, '', $category);
              continue;
            }
          }
          $task['tags'] = $category;
        }
      }

    if (isset($vtodo->{'RELATED-TO'}) && isset($this->tasks[(string)$vtodo->{'RELATED-TO'}])) {
      $relatedTask = $this->tasks[(string)$vtodo->{'RELATED-TO'}];
      $task['depends'] = $relatedTask['uuid'];
    }

    if (isset($vtodo->GEO)){
      $task['geo'] = $vtodo->GEO->getRawMimeDirValue();
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
