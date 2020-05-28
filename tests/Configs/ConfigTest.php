<?php
namespace Aerex\BaikalStorage;

use PHPUnit\Framework\TestCase;
use Aerex\BaikalStorage\Configs\ConfigBuilder;

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


}

