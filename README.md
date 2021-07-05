# baikal-storage-plugin

## Note
Plugin is still a work in progress

## Install
```
composer require aerex/baikal-storage-plugin
```

## Configuration
You can use the CLI to help you generate a config file or use the example configuration provided in the project. Make sure the file is *writable* by your webserver (e.g Apache, Nginx).

### Use the CLI
Run the command `./vendor/baikalstorage` and follow the instructions

### Manual
Copy the `example-config.yaml` file and rename it to `config.yaml`.

## Usage
- Add the plugin to the end of the  `Server.php` file located under `Core/Frameworks/Baikal/Core/Server.php`. For example

```
$this->server->addPlugin(new \Aerex\BaikalStorage\Plugin(<absolute/path/of/config.yaml)
```
