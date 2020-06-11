<?php 

namespace Aerex\BaikalStorage;

use PHPUnit\Framework\TestCase;
use Aerex\BaikalStorage\AbstractConsole;
use Aerex\BaikalStorage\Configs\ConfigBuilder;
use Aerex\BaikalStorage\Storages\Taskwarrior;
use Aerex\BaikalStorage\Logger;
use Sabre\VObject\Component\VCalendar as Calendar;

class StorageManagerTest extends TestCase {

  /**
   * @var \PHPUnit_Framework_MockObject_MockObject   
   * */
  private $mockConsole;

  private $mockStorage;

  public $mockConfigBuilder;

  protected function setUp(): void {
    $this->mockConfigBuilder = $this->getMockBuilder(ConfigBuilder::class)
        ->setMethods(['readContent'])
        ->setConstructorArgs([''])
        ->getMock();
    $this->mockConsole = $this->createMock(AbstractConsole::class);
    $this->mockStorage = $this->createMock(Taskwarrior::class);
    $this->mockLogger = $this->createMock(Logger::class);
    $this->configs = [
      'general' => [
        'logger' => ['file' => '', 'level'=>  'DEBUG', 'enabled' => true],
        'timezone' => 'UTC'
      ], 
      'storages' => [
        'taskwarrior' => ['taskrc' => '', 'taskdata' => '']
      ]
    ];
}

public function testAddTaskwarriorStorage() {
    $tw = new Taskwarrior($this->mockConsole, $this->configs, $this->mockLogger);
    $manager = new StorageManager($this->mockConfigBuilder);
    $manager->addStorage(Taskwarrior::NAME, $tw);
    $storages = $manager->getStorages();
    $configs = $manager->getConfigs();
    $this->assertEquals(sizeof(array_keys($storages)), 1, 'Taskwarrior storage was not added');
    $this->assertArrayHasKey('taskwarrior', $storages, 'Storages should have taskwarrior');
  }

  public function testTaskwarriorImport() {
    $cal = new Calendar();
    $this->mockStorage->expects($this->once())
      ->method('save')
        ->with($this->equalTo($cal));

    $manager = new StorageManager($this->configs);
    $manager->addStorage(Taskwarrior::NAME, $this->mockStorage);
    $manager->import($cal);

  }
}
