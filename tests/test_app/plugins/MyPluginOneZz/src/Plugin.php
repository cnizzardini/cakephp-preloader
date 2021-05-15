<?php
declare(strict_types=1);

namespace MyPluginOneZz;

use Cake\Core\BasePlugin;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\RouteBuilder;

class Plugin extends BasePlugin
{
    /**
     * Plugin name.
     *
     * @var string
     */
    protected $name = 'MyPluginOne';

    public function routes(RouteBuilder $routes): void
    {

    }

    public function middleware(MiddlewareQueue $middleware): MiddlewareQueue
    {
        return $middleware;
    }
}
