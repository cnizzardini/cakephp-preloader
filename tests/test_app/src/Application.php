<?php

namespace CakePreloader\Test\App;

use Cake\Http\BaseApplication;
use Cake\Http\MiddlewareQueue;

class Application extends BaseApplication
{
    public function bootstrap(): void
    {
        $this->addPlugin('CakePreloader');
    }

    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        return $middlewareQueue;
    }
}
