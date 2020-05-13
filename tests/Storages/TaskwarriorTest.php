<?php 

namespace Aerex\BaikalStorage;

use PHPUnit\Framework\TestCase;
use Aerex\BaikalStorage\AbstractConsole;
use Sabre\VObject\Component\VCalendar as Calendar;
use Aerex\BaikalStorage\Storages\Taskwarrior;

class TaskwarriorTest extends TestCase {

  /**
   * @var \PHPUnit_Framework_MockObject_MockObject   
   * */
  private $mockConsole;

  function setUp() {
    $this->mockConsole = $this->createMock(AbstractConsole::class);
  }

  public function testVObjectToTask() {
    $configs = ['taskwarrior' => ['taskrc' => '', 'taskdata' => '']];
    $this->taskwarrior = new Taskwarrior($this->mockConsole, '', $configs);
    $vcalendar = new Calendar([
      'VTODO' => [
        'SUMMARY' => 'Finish project',
        'DTSTAMP' => new \DateTime('2020-07-04 10:00:00'),
        'DTSTART' => new \DateTime('2020-07-04 12:00:00'),
        'DTEND' => new \DateTime('2020-07-05 01:00:00'),
        'DUE'   => new \DateTime('2020-07-05 03:00:00'),
        'LAST_MODIFIED' => new \DateTime('2020-07-04 13:00:00'),
        'PRIORITY' => 5,
        'RRULE' => 'FREQ=MONTHLY'
      ]
    ]);
    echo $vcalendar->VTODO->RRULE->getJsonValue()[0]['freq'];

   $task = $this->taskwarrior->vObjectToTask($vcalendar->VTODO);
   $this->assertArrayHasKey('uid', $task, 'task should have a uid');
   $this->assertEquals((string)$vcalendar->VTODO->UID, $task['uid']);
   $this->assertArrayHasKey('description', $task, 'task should have description');
   $this->assertEquals((string)$vcalendar->VTODO->SUMMARY, $task['description']);
   $this->assertArrayHasKey('due', $task, 'task should have due');
   $this->assertEquals($vcalendar->VTODO->DUE->getDateTime(), $task['due']);
   $this->assertArrayHasKey('entry', $task, 'task should have an entry');
   $this->assertEquals($vcalendar->VTODO->DTSTAMP->getDateTime(), $task['entry']);
   $this->assertArrayHasKey('start', $task, 'task should have start');
   $this->assertEquals($vcalendar->VTODO->DTSTART->getDateTime(), $task['start']);
  }

}
