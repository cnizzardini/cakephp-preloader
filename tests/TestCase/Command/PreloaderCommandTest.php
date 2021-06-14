<?php

namespace CakePreloader\Test\TestCase\Command;

use Cake\Event\Event;
use Cake\Event\EventList;
use Cake\Event\EventManager;
use Cake\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use CakePreloader\Preloader;
use CakePreloader\PreloadResource;
use RuntimeException;

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

    public function test_invalid_file()
    {
        $this->expectException(RuntimeException::class);
        $this->exec('preloader --name="/etc/passwd"');
    }

    public function test_before_write_event()
    {
        $eventManager = EventManager::instance()->setEventList(new EventList());

        $eventManager->on('CakePreloader.beforeWrite', function(Event $event){
            /** @var Preloader $preloader */
            $preloader = $event->getSubject();
            $this->assertInstanceOf(Preloader::class, $preloader);
            $preloader->setPreloadResources([
                (new PreloadResource('require_once', __FILE__))
            ]);
        });

        $this->exec('preloader');
        $this->assertEventFired('CakePreloader.beforeWrite');

        $preload = file_get_contents(ROOT . DS . 'preload.php');
        $this->assertStringContainsString(__FILE__, $preload);
    }
}
