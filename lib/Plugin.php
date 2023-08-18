<?php

namespace Aerex\BaikalStorage;

use Aerex\BaikalStorage\Logger;
use Monolog\Logger as Monolog;
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
     * @var $rawconfigs
     */
    protected $rawConfigs;

    /**
     * @var $config
     */
    protected $config;

    /**
     * @var $logger
     */
    protected $logger;

    /**
     * Creates the Storage plugin
     *
     * @param CalendarProcessor $TWCalManager
     *
     */
    function __construct($configFile){
      $this->rawConfigs = $this->buildConfigurations($configFile);
      $this->logger = new Logger($this->rawConfigs, 'BaikalStorage');
      $this->storageManager = new StorageManager($this->rawConfigs);
      $this->initializeStorages($this->rawConfigs);
    }

    private function getDisplayName($path) {
      // Remove filepath
      $urlParts = explode('/', $path);
      $calendarUrl = implode('/', array_slice($urlParts, 0, sizeof($urlParts)-1));

      // Get displayname from collection
      $properties = $this->server->getProperties($calendarUrl, ['{DAV:}displayname']);
      return $properties['{DAV:}displayname'] ?? '';
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
        $taskwarrior = new Taskwarrior(new Console(['rc.verbose=nothing',
                'rc.hooks=off', 'rc.confirmation=no']),  $configs, new Logger($configs, 'Taskwarrior'));
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

        return 'baikal-storage';

    }
    /**
     * This method is called before any HTTP method handler.
     *
     * This method intercepts any GET, DELETE, PUT and PROPFIND.
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
                break;
            case 'POST':
                $this->httpPost($request, $response);
                break;
            case 'DELETE':
                $this->httpDelete($request);
            return;
        }
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
      $displayname = $this->getDisplayName($request->getPath());
      try {
        if (!$this->storageManager->fromStorageSource($vCal)) {
          $this->storageManager->import($vCal, $displayname);
      } else {
        $this->logger->info('Skipping import');
      }
      } catch(BadRequest $e){
          throw new BadRequest($e->getMessage(), null, $e);
      } catch(\Exception $e){
          throw $e;
      }

      $request->setBody($body);

    }

    /**
     * This method handles the POST method.
     *
     * @param RequestInterface  $request
     *
     */
    function httpPost(RequestInterface $request, ResponseInterface $response) {
        $postVars = $request->getPostData();
        $body = $request->getBodyAsString();
        if (isset($postVars['baikalStorage']))  {
          foreach ($this->storageManager->getStorages() as $storage) {
            if ($storage::NAME == $postVars['baikalStorage']
              && $postVars['baikalStorageAction'] == 'saveConfigs') {
              $updateStorageConfigs = $storage->updateConfigs($postVars);
              $this->rawConfigs['storages'][$postVars['baikalStorage']] = $updateStorageConfigs;
            }
          }

        }
        if (isset($postVars['logLevel'])) {
          $this->rawConfigs['general']['logger']['level'] = $postVars['logLevel'];
        }
        if (isset($postVars['logFilePath'])) {
          $this->rawConfigs['general']['logger']['file'] = $postVars['logFilePath'];
        }

        $this->config->saveConfigs($this->rawConfigs);

        $response->setHeader('Location', $request->getUrl());
        $response->setStatus(302);
        $request->setBody($body);

    }
    /**
     * This method handles the DELETE method.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface  $response
     *
     */

    public function httpDelete(RequestInterface $request) {
      try {
        $body = $request->getBodyAsString();
        $path = $request->getPath();
        $paths = explode('/', $path);
        if (sizeof($paths) > 1) {
          $uid = str_replace('.ics', '', $paths[sizeof($paths)-1]);
          // Attempt to delete if we are removing an ics file
          if ($uid != '') {
            $this->storageManager->remove($uid);
          }
        }
      } catch(BadRequest $e){
        throw new BadRequest($e->getMessage(), null, $e);
      } catch(\Exception $e){
        throw $e;
      }
      $request->setBody($body);

    }



    /**
     * Generates the 'general' configuration section
     * @return string
     */

    public function generateGeneralConfigSection() {
      $configuredLogLevel = '';
      $logFilePath = '';
      if (isset($this->rawConfigs['general'])
        && isset($this->rawConfigs['general']['logger'])
        && $this->rawConfigs['general']['logger']['enabled']) {
        $configuredLogLevel = $this->rawConfigs['general']['logger']['level'];
        $logFilePath = $this->rawConfigs['general']['logger']['file'];
      }
      $html  = '<form method="post" action="">';
      $html .= '<section><h1>Configuration - Baikal Storage</h1>';
      $html .= '<section><h2>general</h2>';
      $html .= '<table class="propTable">';
      $html .= '<tr>';
      $html .= '<th>log level</th>';
      $html .= '<td>The minimum log level </td>';
      $html .=  '<td>';
      $html .= '<select name="logLevel">';

      foreach (Monolog::getLevels() as $key => $value) {
        if ($key == $configuredLogLevel) {
          $selected = ' selected ';
        } else {
          $selected = '';
        }
        $html .= '<option value="'. $key .'"' . $selected . '>'. $key .'</option>';
      }
      $html .= '</select>';

      $html .= '</tr>';
      $html .= '<tr>';
      $html .= '<th>log file path</th>';
      $html .= '<td>The absolute file path of the log</td>';
      $html .= '<td><input name="logFilePath" placeholder="/opt/baikal/log" value='. $logFilePath . ' type="text" id="logFilePath"></input></td>';
      $html .= '</tr>';
      $html .= '<tr>';
      $html .= '</table>';
      $html .= '</section>';
      return $html;
    }

    /**
     * Returns a html to display an optional configuration page for the plugin
     * @return array
     */
    public function getConfigBrowser() {
      $html = $this->generateGeneralConfigSection();

      foreach ($this->storageManager->getStorages() as $storage) {
        $html .= '<section>';
        $html .= '<h2>' . $storage::NAME . '</h2>';
        $html .= '<table class="propTable">';
        $html .= '<input type="hidden" name="baikalStorageAction" value="saveConfigs"></input>';
        $html .= '<input type="hidden" name="baikalStorage" value="taskwarrior"></input>';
        $html .= $storage->getConfigBrowser();
        $html .= '</table>';
        $html .= '</section>';
        $html .= '<input type="submit" value="save"></input>';
        $html .= '</form>';
      }

      return $html;
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
        'description' => 'The plugin provides synchronization between remote storages and iCal todo events',
        'link'        => 'https://git.aerex.me/Aerex/baikal-storage-plugin',
        'config'      => true
      ];

    }
}
