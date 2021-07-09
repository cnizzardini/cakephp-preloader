<?php
declare(strict_types=1);

namespace CakePreloader;

use Cake\Console\CommandCollection;
use Cake\Core\BasePlugin;
use Cake\Core\Configure;
use Cake\Core\PluginApplicationInterface;
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
     * @var bool
     */
    protected $routes = false;

    /**
     * @var bool
     */
    protected $middleware = false;

    /**
     * @param \Cake\Core\PluginApplicationInterface $app PluginApplicationInterface
     * @return void
     */
    public function bootstrap(PluginApplicationInterface $app): void
    {
        if (file_exists(CONFIG . 'preloader_config.php')) {
            Configure::load('preloader_config', 'default');
        }
    }

    /**
     * @param \Cake\Console\CommandCollection $commands CommandCollection
     * @return \Cake\Console\CommandCollection
     */
    public function console(CommandCollection $commands): CommandCollection
    {
        $commands->add('preloader', PreloaderCommand::class);

        return $commands;
    }
}
