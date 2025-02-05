<?php

namespace CakePreloader\Test\TestCase;

use Cake\TestSuite\TestCase;
use CakePreloader\PreloadResource;
use CakePreloader\Exception\ResourceNotFoundException;

class PreloadResourceTest extends TestCase
{
    public function test_invalid_construct()
    {
        $this->expectException(\InvalidArgumentException::class);
        new PreloadResource('nope', '/tmp/test.txt');
    }

    public function test_file_does_not_exist()
    {
        $this->expectException(ResourceNotFoundException::class);
        (new PreloadResource('require_once', '/tmp/test.txt'))->getResource();
    }

    public function test_setPath()
    {
        $resource = new PreloadResource('require_once', '/tmp/test.txt');
        $resource->setPath('/tmp/new_test.txt');
        $this->assertEquals('/tmp/new_test.txt', $resource->getFile());
    }
}
