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

  public function testGeneralLoggerConfigs() {
    $configs = new ConfigBuilder(__DIR__ . '/Fixtures/LoggerConfig.yaml');
    $contents = $configs->loadYaml();
    $this->assertEquals(sizeof($contents), 1);
    $this->assertArrayHasKey('general', $contents, 'config missing general config');
    $generalConfigs = $contents['general'];
    $this->assertArrayHasKey('logger', $generalConfigs, 'general config is missing logger property');
    $this->assertArrayHasKey('file', $generalConfigs['logger'], 'general logger config missing file property');
    $this->assertEquals($generalConfigs['logger']['file'], '/home/user/logger.yaml');
    $this->assertArrayHasKey('level', $generalConfigs['logger'], 'general logger config missing level property');
    $this->assertEquals($generalConfigs['logger']['level'], 'ERROR', 'ERROR is not set as default logger level');
    $this->assertArrayHasKey('enabled', $generalConfigs['logger'], 'general config logger enabled property is missing');
    $this->assertTrue($generalConfigs['logger']['enabled']);
  }

  public function testTaskwarriorConfig() {
    $configs = new ConfigBuilder(__DIR__ . '/Fixtures/TaskwarriorConfig.yaml');
    $configs->add(new TaskwarriorConfig());
    $contents = $configs->loadYaml();
    $this->assertEquals(sizeof($contents), 2);
    $this->assertArrayHasKey('storages', $contents, 'storages config missing');
    $this->assertArrayHasKey('taskwarrior', $contents['storages'], 'storage config missing taskwarrior property');
    $taskwarriorConfigs = $contents['storages']['taskwarrior'];
    $this->assertArrayHasKey('taskrc', $taskwarriorConfigs, 'taskwarrior config is missing taskrc property');
    $this->assertEquals($taskwarriorConfigs['taskrc'], '/home/aerex/.taskrc');
    $this->assertArrayHasKey('taskdata', $taskwarriorConfigs, 'taskwarrior config is missing taskdata property');
    $this->assertEquals($taskwarriorConfigs['taskdata'], '/home/aerex/.task');
    $this->assertArrayHasKey('project_category_prefix', $taskwarriorConfigs, 'taskwarrior config is missing project_category_prefix property');
    $this->assertEquals($taskwarriorConfigs['project_category_prefix'], 'project_');
  }
}

