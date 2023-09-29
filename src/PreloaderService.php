<?php
declare(strict_types=1);

namespace CakePreloader;

use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Core\Configure;
use CakePreloader\Exception\PreloadWriteException;
use SplFileInfo;

class PreloaderService
{
    private Preloader $preloader;

    /**
     * @param \CakePreloader\Preloader|null $preloader Preloader class, an instance is automatically created if null.
     */
    public function __construct(?Preloader $preloader = null)
    {
        $this->preloader = $preloader ?? new Preloader();
    }

    /**
     * Generate preloader. Returns the path on success, otherwise throws exception.
     *
     * @param \Cake\Console\Arguments $args CLI Arguments
     * @param \Cake\Console\ConsoleIo $io ConsoleIo
     * @return string
     * @throws \CakePreloader\Exception\PreloadWriteException
     */
    public function generate(Arguments $args, ConsoleIo $io): string
    {
        $this->cakephp();
        $this->packages($args, $io);
        $this->app($args);
        $this->plugins($args);

        $path = $args->getOption('name') ?? Configure::read('PreloaderConfig.name');
        $path = !empty($path) ? $path : ROOT . DS . 'preload.php';

        $this->preloader->allowCli((bool)$args->getOption('cli'));

        if ($this->preloader->write($path)) {
            return $path;
        }

        throw new PreloadWriteException("Writing to $path failed");
    }

    /**
     * Loads the CakePHP framework
     *
     * @return void
     */
    private function cakephp(): void
    {
        $ignorePaths = implode('|', ['src\/Console', 'src\/Command', 'src\/Shell', 'src\/TestSuite']);

        $this->preloader->loadPath(CAKE, function (SplFileInfo $file) use ($ignorePaths) {
            return !preg_match("/($ignorePaths)/", $file->getPathname());
        });
    }

    /**
     * Adds a list of vendor packages
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io ConsoleIo
     * @return void
     */
    private function packages(Arguments $args, ConsoleIo $io): void
    {
        $packages = $args->getOption('packages') ?? Configure::read('PreloaderConfig.packages');
        if (empty($packages)) {
            return;
        }

        if (is_string($packages)) {
            $packages = explode(',', (string)$args->getOption('packages'));
        }

        $packages = array_map(
            function ($package) {
                return ROOT . DS . 'vendor' . DS . $package;
            },
            $packages
        );

        $validPackages = array_filter($packages, function ($package) {
            if (file_exists($package)) {
                return true;
            }
        });

        if (count($packages) != count($validPackages)) {
            $io->out('<warning>One or more packages not found</warning>');
        }

        foreach ($validPackages as $package) {
            $this->preloader->loadPath($package, function (SplFileInfo $file) use ($args) {
                if ($args->getOption('phpunit')) {
                    return true;
                }

                return !strstr($file->getPath(), '/tests/');
            });
        }
    }

    /**
     * Adds the users APP into the preloader
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @return void
     */
    private function app(Arguments $args): void
    {
        if (($args->hasOption('app') && !$args->getOption('app')) && !Configure::read('PreloaderConfig.app')) {
            return;
        }

        $ignorePaths = ['src\/Console', 'src\/Command'];
        if (!$args->getOption('phpunit')) {
            $ignorePaths[] = 'tests\/';
        }

        $ignorePattern = implode('|', $ignorePaths);

        $this->preloader->loadPath(APP, function (SplFileInfo $file) use ($ignorePattern) {
            return !preg_match("/($ignorePattern)/", $file->getPathname());
        });
    }

    /**
     * Adds the users plugins into the preloader
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @return void
     */
    private function plugins(Arguments $args): void
    {
        $plugins = $args->getOption('plugins') ?? Configure::read('PreloaderConfig.plugins');
        if (empty($plugins)) {
            return;
        }

        $paths = [];
        if ($plugins === '*' || $plugins === true) {
            $paths[] = ROOT . DS . 'plugins';
        } elseif (is_string($plugins)) {
            $plugins = explode(',', (string)$args->getOption('plugins'));
        }

        if (is_array($plugins)) {
            foreach ($plugins as $plugin) {
                $paths[] = ROOT . DS . 'plugins' . DS . $plugin . DS . 'src';
            }
        }

        $ignorePaths = ['src\/Console', 'src\/Command'];
        if (!$args->getOption('phpunit')) {
            $ignorePaths[] = 'tests\/';
        }

        $ignorePattern = implode('|', $ignorePaths);

        foreach ($paths as $path) {
            $this->preloader->loadPath($path, function (SplFileInfo $file) use ($ignorePattern) {
                return !preg_match("/($ignorePattern)/", $file->getPathname());
            });
        }
    }
}
