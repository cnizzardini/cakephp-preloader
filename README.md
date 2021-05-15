# CakePHP Preloader

[![Latest Version on Packagist](https://img.shields.io/packagist/v/cnizzardini/cakephp-preloader.svg?style=flat-square)](https://packagist.org/packages/cnizzardini/cakephp-preloader)
[![Build](https://github.com/cnizzardini/cakephp-preloader/workflows/Build/badge.svg?branch=main)](https://github.com/cnizzardini/cakephp-preloader/actions)
[![Coverage Status](https://coveralls.io/repos/github/cnizzardini/cakephp-preloader/badge.svg?branch=main)](https://coveralls.io/github/cnizzardini/cakephp-preloader?branch=main)
[![License: MIT](https://img.shields.io/badge/license-mit-blue)](LICENSE.md)
[![CakePHP](https://img.shields.io/badge/cakephp-%3E%3D%204.0-red?logo=cakephp)](https://book.cakephp.org/4/en/index.html)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.2-8892BF.svg?logo=php)](https://php.net/)

An OPCache preloader for CakePHP.

Reference: https://www.php.net/manual/en/opcache.preloading.php

This package is meant to provide an easy way for CakePHP application developers to generate preload
files. Goals:

- Generate an OPCache preloader with a simple command.
- Allow optionally loading additional resources such as CakePHP plugins, userland app, and
composer packages.
- Provide a simplistic API for writing a custom preloader.

For an alternative approach, checkout [DarkGhostHunter/Preloader](https://github.com/DarkGhostHunter/Preloader).

For an OPCache UI, checkout [rlerdorf/opcache-status](https://github.com/rlerdorf/opcache-status).

## Installation

You can install this plugin into your CakePHP application using [composer](https://getcomposer.org).

The recommended way to install composer packages is:

```console
composer require cnizzardini/cakephp-preloader
```

Next, load the plugin in your `src/Application.php` `bootstrapCli`
method:

```php
$this->addPlugin('CakePreloader');
```

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
--name          The preload file path. (default:
                /srv/app/preload.php)
--packages      A comma separated list of packages (e.g.
                vendor-name/package-name) to add to the preloader
--plugins       A comma separated list of your plugins to load or `*` to
                load all plugins/*
--quiet, -q     Enable quiet output.
--verbose, -v   Enable verbose output.

```

Examples:

Default only loads in CakePHP core files excluding TestSuite, Console, Command, and Shell namespaces:
```console
bin/cake preloader
```

Include composer packages:
```console
bin/cake preloader --packages=cakephp/authentication,cakephp/chronos
```

Include userland application:
```console
bin/cake preloader --app
```

Include userland plugins:
```console
bin/cake preloader --plugins=*
```

or

```console
bin/cake preloader --plugins=MyPlugin,MyOtherPlugin
```

### Preloader Class

You can build something more elaborate using `Preloader::loadPath()` and `Preloader::write()`. Preloader uses
CakePHP's FileSystem class under the hood.

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

Obviously, these types of benchmarks should be taken with a bit of a gain of salt. I benchmarked this using apache bench with this project here: https://github.com/mixerapi/app which is a dockerized REST API (LEMP stack on alpine + php-fpm 7.4). `DEBUG` was set to false.

```ini
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

Command:

```console
ab -n 10000 -c 10 http://localhost:8080/public/actors.json
```

I ran each 3 times:

| Type      | Run 1 | Run 2 | Run 3 |
| ----------- | ----------- | ----------- | ----------- |
| No Preload | 301.30 [#/sec] (mean) |  335.12 [#/sec] (mean) | 322.41 [#/sec] (mean) |
| `CAKE` only | 447.92 [#/sec] (mean) |  448.48 [#/sec] (mean) | 446.53 [#/sec] (mean) |
| `CAKE` + `APP` | 457.62 [#/sec] (mean) |  455.40 [#/sec] (mean) | 394.89 [#/sec] (mean) |

## Tests / Analysis

Test Suite:

```console
composer test
```

Test Suite + Static Analysis:

```console
composer check
```
