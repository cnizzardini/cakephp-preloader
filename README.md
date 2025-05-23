# CakePHP Preloader

[![Latest Version on Packagist](https://img.shields.io/packagist/v/cnizzardini/cakephp-preloader.svg?style=flat-square)](https://packagist.org/packages/cnizzardini/cakephp-preloader)
[![Build](https://github.com/cnizzardini/cakephp-preloader/actions/workflows/merge.yml/badge.svg)](https://github.com/cnizzardini/cakephp-preloader/actions/workflows/merge.yml)
[![Coverage Status](https://coveralls.io/repos/github/cnizzardini/cakephp-preloader/badge.svg?branch=main)](https://coveralls.io/github/cnizzardini/cakephp-preloader?branch=main)
[![License: MIT](https://img.shields.io/badge/license-mit-blue)](LICENSE.md)
[![CakePHP](https://img.shields.io/badge/cakephp-^5.0-red?logo=cakephp)](https://book.cakephp.org/4/en/index.html)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.1-8892BF.svg?logo=php)](https://php.net/)

An OPCache preloader for CakePHP.

Reference: https://www.php.net/manual/en/opcache.preloading.php

This package is meant to provide an easy way for CakePHP application developers to generate preload
files. Goals:

- Generate an OPCache preloader with a simple command.
- Allow optionally loading additional resources such as CakePHP plugins, userland app, and
composer packages.
- Provide a simplistic API for writing a custom preloader.

For an alternative approach, checkout [DarkGhostHunter/Preloader](https://github.com/DarkGhostHunter/Preloader).

For an OPCache UI, checkout [amnuts/opcache-gui](https://github.com/amnuts/opcache-gui).

The current release is for CakePHP 5 and PHP 8.1, see previous releases for older versions of CakePHP and PHP.

| Version | Branch                                                         | Cake Version | PHP Version | 
|---------|----------------------------------------------------------------|--------------|-------------|
| 1.*     | [main](https://github.com/cnizzardini/cakephp-preloader)       | ^5.0         | ^8.1        |
| 0.*     | [v0](https://github.com/cnizzardini/cakephp-preloader/tree/v0) | ^4.2         | ^7.4        | 

## Installation

You can install this plugin into your CakePHP application using [composer](https://getcomposer.org).

The recommended way to install composer packages is:

```console
composer require cnizzardini/cakephp-preloader
```

Next, load the plugin: 

```shell
bin/cake plugin load CakePreloader --only-cli
```

Or via manual steps in the CakePHP [plugin documentation](https://book.cakephp.org/5/en/plugins.html#loading-a-plugin).

## Usage

The easiest way to use CakePreloader is via the console command. This command can easily be included as
part of your applications build process.

```console
/srv/app $ bin/cake preloader --help
Generate a preload file

Usage:
cake preloader [options]

Options:

--app           Add your applications src directory into the preloader
--help, -h      Display this help.
--name          The preload file path. (default: ROOT . DS . 'preload.php')
--packages      A comma separated list of packages (e.g. vendor-name/package-name) to add to the preloader
--plugins       A comma separated list of your plugins to load or `*` to load all plugins/*
--cli           Should the preloader file exit when run via the php-cli? (default: true)
--quiet, -q     Enable quiet output.
--verbose, -v   Enable verbose output.
```

You may also load configurations from a `config/preloader_config.php` file. Please note, **command line arguments take
precedence**. See [assets/preloader_config.php](assets/preloader_config.php) for a sample configuration file. If you 
prefer handling configurations another way read the CakePHP documentation on
[loading configuration files](https://book.cakephp.org/5/en/development/configuration.html#loading-configuration-files).

### Examples:

Default loads in CakePHP core files excluding TestSuite, Console, Command, and Shell namespaces. The preload file is 
written to `ROOT . DS . 'preload.php'`:

```console
bin/cake preloader
```

Include a list of composer packages:

```console
bin/cake preloader --packages=cakephp/authentication,cakephp/chronos
```

Include your `APP` code:

```console
bin/cake preloader --app
```

Include all your projects plugins:

```console
bin/cake preloader --plugins=*
```

Include a list of your projects plugins:

```console
bin/cake preloader --plugins=MyPlugin,MyOtherPlugin
```

### Before Write Event

You can extend functionality by listening for the `CakePreloader.beforeWrite` event. This is dispatched just before
your preloader file is written.

```php
(\Cake\Event\EventManager::instance())->on('CakePreloader.beforeWrite', function(Event $event){
    /** @var Preloader $preloader */
    $preloader = $event->getSubject();
    $resources = $preloader->getPreloadResources();
    // modify resources or whatever...
    $preloader->setPreloadResources($resources);
});
```

For more on events, read the CakePHP [Events System](https://book.cakephp.org/5/en/core-libraries/events.html#registering-listeners) documentation.

### Preloader Class

You can customize your OPCache Preloader using the same class used by the console command. Preloader uses a port of 
CakePHP 4.x's Filesystem class under the hood.

```php
use CakePreloader\Preloader;

$preloader = new Preloader();
$preloader->loadPath('/required/path/to/files', function (\SplFileInfo $file) {
    // optional call back method, return true to add the file to the preloader
    return true;
});

// default path is ROOT . DS . 'preload.php'
$preloader->write('/optional/path/to/preloader-file.php');
```

## Performance:

Obviously, these types of benchmarks should be taken with a bit of a gain of salt. I benchmarked this using apache 
bench with this project here: https://github.com/mixerapi/demo which is a dockerized REST API 
(LEMP stack on alpine + php-fpm 8.0). CakePHP `DEBUG` was set to false.

```ini
extension=intl.so
extension=pdo_mysql.so
extension=sodium
extension=zip.so
zend_extension=opcache.so

[php]
session.auto_start = Off
short_open_tag = Off
opcache.preload_user=root
opcache.preload=/srv/app/preload.php
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 20000
opcache.memory_consumption = 256
opcache.enable_cli = 0
opcache.enable = 1
opcache.revalidate_freq = 360
opcache.fast_shutdown = 1
realpath_cache_size = 4096K
realpath_cache_ttl = 600
```

Note: `opcache.preload_user=root` and `opcache.preload=/srv/app/preload.php` were disabled for the no preload run.

| Type                      | JSON View (no db)      | JSON View (db select)   |
|---------------------------|------------------------|-------------------------|
| OPCache Only              | 892.69 [#/sec] (mean)  | 805.29 [#/sec] (mean)   |
| OPCache Preload (default) | 1149.08 [#/sec] (mean) | 976.30 [#/sec] (mean)   |


This is 28% more requests per second for JSON responses and 21% more requests per second with JSON + simple SQL select 
when OPCache Preload is enabled.

## Tests / Analysis

Test Suite:

```console
composer test
```

Test Suite + Static Analysis:

```console
composer check
```
