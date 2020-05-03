<?php 

namespace Aerex\BaikalStorage;

use PHPUnit\Framework\TestCase;
use Aerex\BaikalStorage\AbstractConsole;
use Aerex\BaikalStorage\Configs\ConfigBuilder;
use Aerex\BaikalStorage\Configs\TaskwarriorConfig;
use Aerex\BaikalStorage\Storages\Taskwarrior;
use Aerex\BaikalStorage\Storages\IStorage;
use Sabre\VObject\Component\VCalendar as Calendar;

class StorageManagerTest extends TestCase {

  /**
   * @var \PHPUnit_Framework_MockObject_MockObject   
   * */
  private $mockConsole;

  private $mockStorage;

  public $mockConfigBuilder;

  function setUp() {
    $this->mockConfigBuilder = $this->getMockBuilder(ConfigBuilder::class)
        ->setMethods(['readContent'])
        ->setConstructorArgs([''])
        ->getMock();
    $this->mockConsole = $this->createMock(AbstractConsole::class);
    $this->mockStorage = $this->createMock(IStorage::class);
  }

  public function testAddTaskwarriorStorage() {
    $this->mockConfigBuilder->expects($this->once())
        ->method('readContent')
        ->willReturn(file_get_contents(__DIR__ . '/Fixtures/taskwarrior_config.yml'));
    $tw = new Taskwarrior($this->mockConsole, new TaskwarriorConfig());
    $manager = new StorageManager($this->mockConfigBuilder);
    $manager->addStorage(Taskwarrior::NAME, $tw);
    $storages = $manager->getStorages();
    $manager->init();
    $configs = $manager->getConfigs();
    $this->assertEquals(sizeof(array_keys($storages)), 1, 'Taskwarrior storage was not added');
    $this->assertEquals(sizeof(array_keys($configs)), 1, 'Taskwarrior config was not loaded'); 
    $this->assertArrayHasKey('taskwarrior', $storages, 'Storages should have taskwarrior');
    $this->assertArrayHasKey('taskwarrior', $configs, 'Configs should have taskwarrior');
  }

  public function testTaskwarriorImport() {
    $cal = new Calendar();
    $this->mockConfigBuilder->expects($this->once())
        ->method('readContent')
        ->willReturn(file_get_contents(__DIR__ . '/Fixtures/taskwarrior_config.yml'));
    $this->mockStorage->expects($this->once())
      ->method('save')
        ->with($this->equalTo($cal));
    $this->mockStorage->expects($this->once())
      ->method('setRawConfigs')
        ->with($this->equalTo(['data_dir' => '~/.task']));
    $this->mockStorage->expects($this->once())
        ->method('getConfig')
          ->willReturn(new TaskwarriorConfig());

    $manager = new StorageManager($this->mockConfigBuilder);
    $manager->addStorage(Taskwarrior::NAME, $this->mockStorage);
    $manager->init();

    $manager->import($cal);

  }
}
