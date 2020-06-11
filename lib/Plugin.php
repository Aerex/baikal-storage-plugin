<?php

namespace Aerex\BaikalStorage;

use Aerex\BaikalStorage\Logger;
use Aerex\BaikalStorage\Storages\Taskwarrior;
use Aerex\BaikalStorage\Configs\ConfigBuilder;
use Aerex\BaikalStorage\Configs\TaskwarriorConfig;
use Sabre\DAV\Exception\BadRequest;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Sabre\DAV\ServerPlugin;
use Sabre\DAV\Server;

/**
 * The plugin to interact with Baikal and external storages 
 *
 */
class Plugin extends ServerPlugin {

    /**
     * Reference to server object.
     *
     * @var Server
     */
    protected $server;



    /**
     * @var StorageManager
     */
    protected $storageManager;

    
    /**
     * Creates the Storage plugin
     *
     * @param CalendarProcessor $TWCalManager
     *
     */
    function __construct($configFile){
      $configs = $this->buildConfigurations($configFile);
      $this->storageManager = new StorageManager($configs);
      $this->initializeStorages($configs);
    }

    public function buildConfigurations($configFile) {
      $this->config = new ConfigBuilder($configFile);
      $this->config->add(new TaskwarriorConfig());
      return $this->config->loadYaml();
    }

    /**
     * Configure available storages in storage manager
     *
     */

    public function initializeStorages($configs) {
      $taskwarrior = new Taskwarrior(new Console(['rc.verbose=nothing', 'rc.hooks=off']),  $configs, new Logger($configs, 'Taskwarrior'););
      $this->storageManager->addStorage(Taskwarrior::NAME, $taskwarrior); 
    }

    /**
     * Sets up the plugin
     *
     * @param Server $server
     * @return void */
    function initialize(Server $server) {
        $this->server = $server;
        $server->on('beforeMethod:*',         [$this, 'beforeMethod'], 15);

    }

    /**
     * Returns a plugin name.
     *
     * Using this name other plugins will be able to access other plugins
     * using DAV\Server::getPlugin
     *
     * @return string
     */
    function getPluginName() {

        return 'taskwarrior';

    }
    /**
     * This method is called before any HTTP method handler.
     *
     * This method intercepts any GET, DELETE, PUT and PROPFIND calls to
     * filenames that are known to match the 'temporary file' regex.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     *
     * @return bool
     */
    public function beforeMethod(RequestInterface $request, ResponseInterface $response)
    {

        switch ($request->getMethod()) {
            case 'PUT':
                $this->httpPut($request, $response);
        }
        return;
    }
    
    

    /**
     * This method handles the PUT method.
     *
     * @param RequestInterface  $request
     *
     * @return bool
     */
    function httpPut(RequestInterface $request){
      $body = $request->getBodyAsString();
      $vCal = \Sabre\VObject\Reader::read($body);
      try {
        $this->storageManager->import($vCal);
      } catch(BadRequest $e){
          throw new BadRequest($e->getMessage(), null, $e);
      } catch(\Exception $e){
          throw new \Exception($e->getMessage(), null, $e);
      }

      $request->setBody($body);

    }

    /**
     * Returns a bunch of meta-data about the plugin.
     *
     * Providing this information is optional, and is mainly displayed by the
     * Browser plugin.
     *
     * The description key in the returned array may contain html and will not
     * be sanitized.
     *
     * @return array
     */
    function getPluginInfo() {

      return [
        'name'        => $this->getPluginName(),
        'description' => 'The plugin provides synchronization between taskwarrior tasks and iCAL events',
        'link'        => null,
      ];

    }
}
