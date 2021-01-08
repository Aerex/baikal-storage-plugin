<?php


namespace Aerex\BaikalStorage\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Monolog\Logger as Monolog;
use Aerex\BaikalStorage\Configs\ConfigBuilder;



class CreateConfigFileCommand extends Command {
  protected $io;

  protected static $defaultName = 'app:create-config';
  protected static $CONFIG_FILE_NAME = 'config.yaml';
  protected static $LOGGER_FILE_NAME = 'log';
  private $configs = [];
  private $fullFilePath;

  public function addtaskwarriorConfig() {
    $configs = [];
    $filePath = $this->io->askQuestion(new Question('Where is the location for the taskrc file?'));
    $taskrcFilePath = $filePath . '/.taskrc';
    if (!file_exists($taskrcFilePath)) {
      throw new \RuntimeException("The taskrc file  at $taskrcFilePath does not exist");
    }
    $configs['taskrc'] = $taskrcFilePath;
    $filePath = $this->io->askQuestion(new Question('Where is the data location (taskdata)'));
    $taskDataFilePath = $filePath;
    if (!file_exists($taskDataFilePath)) { 
      throw new \RuntimeException("The task.data location $taskDataFilePath does not exist");
    } 
    $configs['taskdata'] = $taskDataFilePath;
    $displayName = $this->io->askQuestion(new Question('What is the displayname for the default calendar (e.g default)'));
    $configs['default_calendar'] = isset($displayName) ? $displayName : 'default'; 
    $this->configs['storages']['taskwarior'] = $configs;
  }

  protected function configure() {
    $this
      ->setName('create-config')
      ->setDescription('Create baikal storage plugin configuration file');
    }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->io = new SymfonyStyle($input, $output);

    $this->io->title('Baikal Storage Plugin Configuration Creation');

    $filePath = $this->io->askQuestion(new Question('Where to create `config.yaml` configuration file?'));
    $this->fullFilePath = $this->verifyFileCanBeCreated($filePath, CreateConfigFileCommand::$CONFIG_FILE_NAME);

    $this
      ->setupGeneralConfigs()
      ->setupStorages()
      ->saveConfigs();

    // or return this if some error happened during the execution
    // (it's equivalent to returning int(1))
    // return Command::FAILURE;
  }

  protected function getStorageNames() {
    $storageDir = __DIR__ . '/../Storages';
    $storageFiles = scandir($storageDir);
    $storages = array_values(array_filter($storageFiles, function($storageFile) use ($storageDir) {
      return !preg_match('/^IStorage/', $storageFile) && !is_dir("$storageDir/$storageFile");
    }));
    array_walk($storages, function(&$storage) {
      $storage = strtolower(preg_replace('/\.php/', '', $storage));
    }); 
    return $storages;
  }

  protected function verifyFileCanBeCreated($filePath, $fileName) {
    if (empty($filePath)) {
      throw new \RuntimeException('Configuration file path cannot be empty'); 
    }

    if (!is_dir($filePath)) {
      throw new \RuntimeException("File path $filePath does not exist");
    }

    if ((strrpos($filePath, '/') + 1) === strlen($filePath)) {
      $fullFilePath = substr($filePath, 0, -1) . $fileName;
    } else {
      $fullFilePath = $filePath . '/' . $fileName;
    }

    if (file_exists($fullFilePath)) {
      throw new \RuntimeException("The configuration file at $this->fullFilePath already exists");
    }
     return $fullFilePath;
  }

  protected function setupGeneralConfigs() {
    $this->io->section('Setting up general configurations');
    $this->configs['general'] = [];
    if ($this->io->confirm('Enable logging?')) {
      $this->configs['general']['logger'] = [];
      $logFilePath = $this->io->askQuestion(new Question('Where to create log file?'));
      $this->configs['general']['logger']['file'] = $this->verifyFileCanBeCreated($logFilePath, CreateConfigFileCommand::$LOGGER_FILE_NAME);

      $logLevelChoiceQuestion = new ChoiceQuestion('Log level (defaults to ERROR)', array_keys(Monolog::getLevels()), 4);
      $logLevelChoiceQuestion->setErrorMessage('Log level %s is invalid');
      $logLevel = $this->io->askQuestion($logLevelChoiceQuestion);
      $this->configs['general']['logger']['level'] = $logLevel;
    }
    return $this;
  }

  protected function setupStorages() {
    $this->configs['storages'] = [];
    $this->io->section('Setup storage configurations');
    $storagesMultiSelectQuestion = new ChoiceQuestion('Select the storages to add', $this->getStorageNames());
    $storagesMultiSelectQuestion->setMultiselect(true);
    $storagesMultiSelectQuestion->setErrorMessage('Storages %s is not supported');
    $storages = $this->io->askQuestion($storagesMultiSelectQuestion);
    foreach ($storages as $storage) {
      $methodName = "add${storage}Config";
      $storageConfigMethod = new \ReflectionMethod(CreateConfigFileCommand::class, $methodName);
      $storageConfigMethod->invoke($this);
    }
    return $this;
  } 

  protected function saveConfigs() {
    $configBuilder = new ConfigBuilder($this->fullFilePath);
    $configBuilder->saveConfigs($this->configs);
  }

}
