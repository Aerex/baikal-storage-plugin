<?php
namespace Aerex\BaikalStorage;

use PHPUnit\Framework\TestCase;
use Aerex\BaikalStorage\Configs\ConfigBuilder;
use Aerex\BaikalStorage\Configs\TaskwarriorConfig;

class ConfigTest extends TestCase {

  /**
   * @var \PHPUnit_Framework_MockObject_MockObject   
   * */

  public $mockConfigBuilder;

  public function testLoggerConfigs() {
    $configs = new ConfigBuilder(__DIR__ . '/Fixtures/LoggerConfig.yaml');
    $contents = $configs->loadYaml();
    $this->assertEquals(sizeof($contents), 1);
    $this->assertArrayHasKey('logger', $contents, 'config missing logger property');
    $this->assertArrayHasKey('file', $contents['logger'], 'config missing logger.file property');
    $this->assertEquals($contents['logger']['file'], '/home/user/logger.yaml');
    $this->assertArrayHasKey('level', $contents['logger'], 'config missing logger.level property');
    $this->assertEquals($contents['logger']['level'], 'ERROR', 'ERROR is not set as default logger level');
    $this->assertArrayHasKey('enabled', $contents['logger'], 'config missing logger.enabled property');
    $this->assertTrue($contents['logger']['enabled']);

  }

  public function testTaskwarriorConfig() {
    $configs = new ConfigBuilder(__DIR__ . '/Fixtures/TaskwarriorConfig.yaml');
    $configs->add(new TaskwarriorConfig());
    $contents = $configs->loadYaml();
    $this->assertEquals(sizeof($contents), 2);
    $this->assertArrayHasKey('logger', $contents, 'config missing logger property');
    $this->assertArrayHasKey('file', $contents['logger'], 'config missing logger.file property');
    $this->assertArrayHasKey('level', $contents['logger'], 'config missing logger.level property');
    $this->assertArrayHasKey('enabled', $contents['logger'], 'config missing logger.enabled property');
    $this->assertArrayHasKey('taskwarrior', $contents, 'config missing taskwarrior property');
    $this->assertArrayHasKey('taskrc', $contents['taskwarrior'], 'config missing taskwarrior.taskrc property');
    $this->assertEquals($contents['taskwarrior']['taskrc'], '/home/aerex/.taskrc');
    $this->assertArrayHasKey('taskdata', $contents['taskwarrior'], 'config missing taskwarrior.taskdata property');
    $this->assertEquals($contents['taskwarrior']['taskdata'], '/home/aerex/.task');
    $this->assertArrayHasKey('project_tag_suffix', $contents['taskwarrior'], 'config missing taskwarrior.taskdata property');
    $this->assertEquals($contents['taskwarrior']['project_tag_suffix'], 'project_');
  }


}

