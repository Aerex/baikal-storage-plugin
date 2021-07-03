# baikal-storage-plugin

## Note
Plugin is still a work in progress

## Install
```
composer require aerex/baikal-storage-plugin
```

## Configuration
Create the `config.yaml` to your webserver. Make sure the file is *writable* by your webserver (e.g Apache, Nginx). For more details on the configuration details see the wiki page.

## Usage
- Add the plugin to `Core/Frameworks/Baikal/Core/Server.php` 
```
$this->server->addPlugin(new \Aerex\BaikalStorage\Plugin(<absolute/path/of/config/file>))
```
