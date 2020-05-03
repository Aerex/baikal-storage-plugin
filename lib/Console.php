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
  }

  public function execute($cmd, $args, $input = null) {
    $stdin[] = $cmd;
    $stdin[] = array_merge($stdin, $this->defaultArgs, $args); 

    if (isset($input)) {
      $stdin[] = $this->convertToString($input);
      $process = new Process($stdin);
    }

    try {
      $process->mustRun();
      return $process->getOutput();
    } catch (ProcessFailedException $error) {
      echo $error->getMessage();
      throw $error;
    }
  }
}

