<?php

namespace CakePreloader\Test\TestCase;

use Cake\Core\Plugin;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use CakePreloader\Test\App\Application;

class PluginTest extends TestCase
{
    use IntegrationTestTrait;

    public function setUp(): void
    {
        parent::setUp();
        static::setAppNamespace('CakePreloader\Test\App');
    }

    public function test_bootstrap()
    {
        (new Application(CONFIG))->bootstrap();
        $this->assertTrue(in_array('CakePreloader', Plugin::loaded()));
    }
}
