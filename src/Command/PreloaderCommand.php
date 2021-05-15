<?php
declare(strict_types=1);

namespace CakePreloader\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use CakePreloader\Preloader;
use SplFileInfo;

/**
 * Generates a preload file
 *
 * This can be included as part of your build process.
 *
 * @see https://www.php.net/manual/en/opcache.preloading.php
 */
class PreloaderCommand extends Command
{
    /**
     * @var \CakePreloader\Preloader
     */
    private $preloader;

    /**
     * PreloaderCommand constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->preloader = new Preloader();
    }

    /**
     * Generates a preload file
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $this->cakephp();
        $this->packages($args);
        $this->applications($args);
        $name = (string)$args->getOption('name');

        $result = $this->preloader->write($name);

        if ($result) {
            $io->hr();
            $io->success('Preload written to ' . $name);
            $io->out('You must restart your PHP service for the changes to take effect.');
            $io->hr();

            return static::CODE_SUCCESS;
        }

        $io->err('Error encountered writing to ' . $name);

        return static::CODE_ERROR;
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
     * @return void
     */
    private function packages(Arguments $args): void
    {
        if (empty($args->getOption('packages'))) {
            return;
        }

        $packages = explode(',', (string)$args->getOption('packages'));
        if (empty($packages)) {
            triggerWarning('Package list is empty');

            return;
        }

        $packages = array_map(
            function ($package) {
                return ROOT . DS . 'vendor' . DS . $package;
            },
            $packages
        );

        $packages = array_filter($packages, function ($package) {
            if (file_exists($package)) {
                return true;
            }
            triggerWarning("Package $package could not be located");
        });

        foreach ($packages as $package) {
            $this->preloader->loadPath($package, function (SplFileInfo $file) use ($args) {
                if ($args->getOption('phpunit')) {
                    return true;
                }

                return !strstr($file->getPath(), '/tests/');
            });
        }
    }

    /**
     * Adds the users APP and plugins into the preloader
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @return void
     */
    private function applications(Arguments $args): void
    {
        $apps = $args->getOption('app') ? [APP] : [];

        if ($args->getOption('plugins') === '*') {
            $apps[] = ROOT . DS . 'plugins';
        } elseif (!empty($args->getOption('plugins')) && is_string($args->getOption('plugins'))) {
            foreach (explode(',', $args->getOption('plugins')) as $plugin) {
                $apps[] = ROOT . DS . 'plugins' . DS . $plugin . DS . 'src';
            }
        }

        $ignorePaths = ['src\/Console', 'src\/Command'];
        if (!$args->getOption('phpunit')) {
            $ignorePaths[] = 'tests\/';
        }

        $ignorePattern = implode('|', $ignorePaths);

        foreach ($apps as $app) {
            $this->preloader->loadPath($app, function (SplFileInfo $file) use ($ignorePattern) {
                return !preg_match("/($ignorePattern)/", $file->getPathname());
            });
        }
    }

    /**
     * Get the option parser.
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The option parser to update
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser
            ->setDescription('Generate a preload file')
            ->addOption('name', [
                'help' => 'The preload file path.',
                'default' => ROOT . DS . 'preload.php',
            ])
            ->addOption('app', [
                'help' => 'Add your applications src directory into the preloader',
                'boolean' => true,
                'default' => false,
            ])
            ->addOption('plugins', [
                'help' => 'A comma separated list of your plugins to load or `*` to load all plugins/*',
            ])
            ->addOption('packages', [
                'help' => 'A comma separated list of packages (e.g. vendor-name/package-name) to add to the preloader',
            ])
            ->addOption('phpunit', [
                'help' => 'For this packages internal test suite only',
                'boolean' => true,
                'default' => false,
            ]);

        return $parser;
    }
}
