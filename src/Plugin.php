<?php
declare(strict_types=1);

namespace CakePreloader;

use Cake\Console\CommandCollection;
use Cake\Core\BasePlugin;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\RouteBuilder;
use CakePreloader\Command\PreloaderCommand;

/**
 * Plugin for Preloader
 */
class Plugin extends BasePlugin
{
    /**
     * Plugin name.
     *
     * @var string
     */
    protected $name = 'CakePreloader';

    /**
     * @param \Cake\Console\CommandCollection $commands CommandCollection
     * @return \Cake\Console\CommandCollection
     */
    public function console(CommandCollection $commands): CommandCollection
    {
        $commands->add('preloader', PreloaderCommand::class);

        return $commands;
    }

    /**
     * @param \Cake\Routing\RouteBuilder $routes An instance of RouteBuilder
     * @return void
     */
    public function routes(RouteBuilder $routes): void
    {
    }

    /**
     * @param \Cake\Http\MiddlewareQueue $middleware An instance of MiddlewareQueue
     * @return \Cake\Http\MiddlewareQueue
     */
    public function middleware(MiddlewareQueue $middleware): MiddlewareQueue
    {
        return $middleware;
    }
}
