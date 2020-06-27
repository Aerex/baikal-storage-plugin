<?php 

namespace Aerex\BaikalStorage;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException as ProcessFailedException;

class Console extends AbstractConsole {
  private $defaultArgs = [];

  function __construct($defaultArgs) {
    $this->defaultArgs = $defaultArgs;
  }

  private function convertToString($input) {
    if (is_array($input)) {
      return json_encode($input); 
    }
    return $input;
  }

  public function execute($cmd, $args, $input = null, $envs = []) {
    $stdin[] = $cmd;
    $stdin = array_merge($stdin, $this->defaultArgs, $args); 

    if (isset($input)) {
      $input = $this->convertToString($input);
    }
    $process = new Process(implode(' ', $stdin), null, $envs, $input);
    $process->inheritEnvironmentVariables();

    try {
      $process->mustRun();
      return $process->getOutput();
    } catch (ProcessFailedException $error) {
      throw $error;
    }
  }
}

