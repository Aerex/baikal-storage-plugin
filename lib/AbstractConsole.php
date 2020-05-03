<?php

namespace Aerex\BaikalStorage;

abstract class AbstractConsole {

  function __construct($defaultArgs) {
    $this->defaultArgs = $defaultArgs;
  }

  abstract protected function execute($cmd, $args, $input = null);
}
