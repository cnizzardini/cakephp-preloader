<?php

namespace CakePreloader\Test\TestCase\Command;

use Cake\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;

class PreloaderCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    public function setUp() : void
    {
        parent::setUp();
        $this->setAppNamespace('CakePreloader\Test\App');
        $this->useCommandRunner();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        @unlink(ROOT . DS . 'preload.php');
    }

    public function test_default()
    {
        $this->exec('preloader');
        $this->assertExitSuccess();
        $this->assertFileExists(ROOT . DS . 'preload.php');

        $preload = file_get_contents(ROOT . DS . 'preload.php');

        $lines = [
            'vendor/autoload.php',
            'vendor/cakephp/cakephp/src/Cache/Cache.php',
            'vendor/cakephp/cakephp/src/basics.php',
            'require_once',
            'opcache_compile_file'
        ];

        foreach ($lines as $file) {
            $this->assertStringContainsString($file, $preload);
        }
    }

    public function test_with_name()
    {
        $name = ROOT . DS . 'a-unique-name.php';

        $this->exec('preloader --name="' . $name . '"');
        $this->assertExitSuccess();
        $this->assertFileExists($name);
        @unlink(ROOT . DS . 'a-unique-name.php');
    }

    public function test_with_app()
    {
        $this->exec('preloader --app --phpunit');
        $this->assertExitSuccess();

        $preload = file_get_contents(ROOT . DS . 'preload.php');
        $this->assertStringContainsString('test_app/src/Application.php', $preload);
    }

    public function test_with_one_plugin()
    {
        $this->exec('preloader --plugins=MyPluginOneZz --phpunit');
        $preload = file_get_contents(ROOT . DS . 'preload.php');
        $this->assertStringContainsString('plugins/MyPluginOneZz/src/Plugin.php', $preload);
    }

    public function test_with_multiple_plugins()
    {
        $this->exec('preloader --plugins=MyPluginOneZz,MyPluginTwoZz --phpunit');
        $preload = file_get_contents(ROOT . DS . 'preload.php');
        $this->assertStringContainsString('plugins/MyPluginOneZz/src/Plugin.php', $preload);
        $this->assertStringContainsString('plugins/MyPluginTwoZz/src/Plugin.php', $preload);
    }

    public function test_with_plugins_wildcard()
    {
        $this->exec('preloader --plugins=* --phpunit');
        $preload = file_get_contents(ROOT . DS . 'preload.php');
        $this->assertStringContainsString('plugins/MyPluginOneZz/src/Plugin.php', $preload);
        $this->assertStringContainsString('plugins/MyPluginTwoZz/src/Plugin.php', $preload);
    }

    public function test_with_one_package()
    {
        $this->exec('preloader --packages=vendorone/packageone --phpunit');
        $this->assertExitSuccess();

        $preload = file_get_contents(ROOT . DS . 'preload.php');
        $this->assertStringContainsString('vendorone/packageone/src/VendorOnePackageOneTestClassZz.php', $preload);
    }

    public function test_with_multiple_packages()
    {
        $this->exec('preloader --packages=vendorone/packageone,vendortwo/packagetwo --phpunit');
        $this->assertExitSuccess();

        $preload = file_get_contents(ROOT . DS . 'preload.php');
        $this->assertStringContainsString('vendorone/packageone/src/VendorOnePackageOneTestClassZz.php', $preload);
        $this->assertStringContainsString('vendortwo/packagetwo/src/VendorTwoPackageTwoTestClassZz.php', $preload);
    }
}
