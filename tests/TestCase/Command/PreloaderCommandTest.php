<?php
declare(strict_types=1);

namespace CakePreloader\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Event\EventList;
use Cake\Event\EventManager;
use Cake\TestSuite\TestCase;
use CakePreloader\Exception\PreloadWriteException;
use CakePreloader\Preloader;
use CakePreloader\PreloaderService;
use CakePreloader\PreloadResource;
use RuntimeException;

class PreloaderCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    public function setUp(): void
    {
        parent::setUp();
        $this->setAppNamespace('CakePreloader\Test\App');

        $this->mockService(PreloaderService::class, function () {
            return new PreloaderService(new Preloader());
        });
    }

    public function tearDown(): void
    {
        parent::tearDown();
        if (file_exists(ROOT . DS . 'preload.php')) {
            unlink(ROOT . DS . 'preload.php');
        }
        if (file_exists(ROOT . DS . 'a-unique-name.php')) {
            unlink(ROOT . DS . 'a-unique-name.php');
        }
    }

    public function test_default(): void
    {
        $this->exec('preloader');
        $this->assertExitSuccess();
        $this->assertFileExists(ROOT . DS . 'preload.php');

        /** @var string $preload */
        $preload = file_get_contents(ROOT . DS . 'preload.php');
        $this->assertTrue(is_string($preload));

        $this->assertStringContainsString("if (in_array(PHP_SAPI, ['cli', 'phpdbg'], true))", $preload);

        $contains = [
            'vendor/autoload.php',
            'vendor/cakephp/cakephp/src/Cache/Cache.php',
            'require_once',
            //'opcache_compile_file',
        ];

        foreach ($contains as $file) {
            $this->assertStringContainsString($file, $preload);
        }

        $excludes = [
            'vendor/cakephp/cakephp/src/Database/Exception.php',
            'vendor/cakephp/cakephp/src/Database/Expression/Comparison.php',
            'vendor/cakephp/cakephp/src/Http/ControllerFactory.php',
            'vendor/cakephp/cakephp/src/Routing/Exception/MissingControllerException.php',
        ];

        foreach ($excludes as $file) {
            $this->assertStringNotContainsString($file, $preload);
        }
    }

    public function test_cli(): void
    {
        $this->exec('preloader --cli');
        $this->assertExitSuccess();
        $this->assertFileExists(ROOT . DS . 'preload.php');

        /** @var string $preload */
        $preload = file_get_contents(ROOT . DS . 'preload.php');
        $this->assertTrue(is_string($preload));
        $this->assertStringContainsString("if (in_array(PHP_SAPI, ['phpdbg'], true))", $preload);
    }

    public function test_with_name(): void
    {
        $path = ROOT . DS . 'a-unique-name.php';

        $this->exec('preloader --name="' . $path . '"');
        $this->assertExitSuccess();
        $this->assertFileExists($path);
    }

    public function test_with_app(): void
    {
        $this->exec('preloader --app --phpunit');
        $this->assertExitSuccess();
        /** @var string $preload */
        $preload = file_get_contents(ROOT . DS . 'preload.php');
        $this->assertStringContainsString('test_app/src/Application.php', $preload);
    }

    public function test_with_one_plugin(): void
    {
        $this->exec('preloader --plugins=MyPluginOneZz --phpunit');
        /** @var string $preload */
        $preload = file_get_contents(ROOT . DS . 'preload.php');
        $this->assertStringContainsString('plugins/MyPluginOneZz/src/Plugin.php', $preload);
    }

    public function test_with_multiple_plugins(): void
    {
        $this->exec('preloader --plugins=MyPluginOneZz,MyPluginTwoZz --phpunit');
        /** @var string $preload */
        $preload = file_get_contents(ROOT . DS . 'preload.php');
        $this->assertStringContainsString('plugins/MyPluginOneZz/src/Plugin.php', $preload);
        $this->assertStringContainsString('plugins/MyPluginTwoZz/src/Plugin.php', $preload);
    }

    public function test_with_plugins_wildcard(): void
    {
        $this->exec('preloader --plugins=* --phpunit');
        /** @var string $preload */
        $preload = file_get_contents(ROOT . DS . 'preload.php');
        $this->assertStringContainsString('plugins/MyPluginOneZz/src/Plugin.php', $preload);
        $this->assertStringContainsString('plugins/MyPluginTwoZz/src/Plugin.php', $preload);
    }

    public function test_with_one_package(): void
    {
        $this->exec('preloader --packages=vendorone/packageone --phpunit');
        $this->assertExitSuccess();

        /** @var string $preload */
        $preload = file_get_contents(ROOT . DS . 'preload.php');
        $this->assertStringContainsString('vendorone/packageone/src/VendorOnePackageOneTestClassZz.php', $preload);
    }

    public function test_with_multiple_packages(): void
    {
        $this->exec('preloader --packages=vendorone/packageone,vendortwo/packagetwo --phpunit');
        $this->assertExitSuccess();

        /** @var string $preload */
        $preload = file_get_contents(ROOT . DS . 'preload.php');
        $this->assertStringContainsString('vendorone/packageone/src/VendorOnePackageOneTestClassZz.php', $preload);
        $this->assertStringContainsString('vendortwo/packagetwo/src/VendorTwoPackageTwoTestClassZz.php', $preload);
    }

    public function test_before_write_event(): void
    {
        $eventManager = EventManager::instance()->setEventList(new EventList());

        $eventManager->on('CakePreloader.beforeWrite', function (Event $event) {
            /** @var Preloader $preloader */
            $preloader = $event->getSubject();
            $this->assertInstanceOf(Preloader::class, $preloader);
            $preloader->setPreloadResources([
                (new PreloadResource('require_once', __FILE__)),
            ]);
        });

        $this->exec('preloader');
        $this->assertEventFired('CakePreloader.beforeWrite');

        /** @var string $preload */
        $preload = file_get_contents(ROOT . DS . 'preload.php');
        $this->assertStringContainsString(__FILE__, $preload);
    }

    public function test_with_config(): void
    {
        Configure::load('preloader_config_test', 'default');

        $path = ROOT . DS . 'a-unique-name.php';

        $this->exec('preloader --phpunit');
        $this->assertExitSuccess();
        $this->assertFileExists($path);

        /** @var string $preload */
        $preload = file_get_contents($path);

        // app
        $this->assertStringContainsString('test_app/src/Application.php', $preload);

        // plugins
        $this->assertStringContainsString('plugins/MyPluginOneZz/src/Plugin.php', $preload);
        $this->assertStringContainsString('plugins/MyPluginTwoZz/src/Plugin.php', $preload);

        // packages
        $this->assertStringContainsString('vendorone/packageone/src/VendorOnePackageOneTestClassZz.php', $preload);
        $this->assertStringContainsString('vendortwo/packagetwo/src/VendorTwoPackageTwoTestClassZz.php', $preload);
    }

    public function test_invalid_file(): void
    {
        $this->expectException(RuntimeException::class);
        $this->exec('preloader --name="/etc/passwd"');
    }

    public function test_package_warning(): void
    {
        $this->exec('preloader --packages=a,b --phpunit');
        $this->assertOutputContains('One or more packages not found');
    }

    public function test_write_exception(): void
    {
        $this->mockService(PreloaderService::class, function () {
            $mock = $this->createPartialMock(Preloader::class, ['write']);
            $mock
                ->method('write')
                ->willThrowException(new PreloadWriteException());

            return new PreloaderService($mock);
        });

        $this->exec('preloader --phpunit');
        $this->assertExitError();
    }

    public function test_with_base_path(): void
    {
        $basePath = '/custom/base/path';
        $this->exec('preloader --basePath="' . $basePath . '"');
        $this->assertExitSuccess();
        $this->assertFileExists($basePath . DS . 'preload.php');

        /** @var string $preload */
        $preload = file_get_contents($basePath . DS . 'preload.php');
        $this->assertTrue(is_string($preload));

        $this->assertStringContainsString("if (in_array(PHP_SAPI, ['cli', 'phpdbg'], true))", $preload);

        $contains = [
            'vendor/autoload.php',
            'vendor/cakephp/cakephp/src/Cache/Cache.php',
            'require_once',
            //'opcache_compile_file',
        ];

        foreach ($contains as $file) {
            $this->assertStringContainsString($file, $preload);
        }

        $excludes = [
            'vendor/cakephp/cakephp/src/Database/Exception.php',
            'vendor/cakephp/cakephp/src/Database/Expression/Comparison.php',
            'vendor/cakephp/cakephp/src/Http/ControllerFactory.php',
            'vendor/cakephp/cakephp/src/Routing/Exception/MissingControllerException.php',
        ];

        foreach ($excludes as $file) {
            $this->assertStringNotContainsString($file, $preload);
        }
    }
}
