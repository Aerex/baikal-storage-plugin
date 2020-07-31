# baikal-storage-plugin

## Install
```
composer require aerex/baikal-storage-plugin
```

## Configuration
Copy sample configuration to your baikal installation. Make sure that the folder is *writable* by your webserver (e.g Apache, Nginx)

## Usage
- Add the plugin to `Core/Frameworks/Baikal/Core/Server.php` 
```
$this->server->addPlugin(new \Aerex\BaikalStorage\Plugin(<path-of-config-file>))
```
