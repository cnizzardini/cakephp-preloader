<?php
declare(strict_types=1);

namespace CakePreloader\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
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
    private Preloader $preloader;

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
        $io->out('Generating preloader...');

        $this->cakephp();
        $this->packages($args);
        $this->app($args);
        $this->plugins($args);

        $io->info('Preloader Config: ' . (Configure::check('PreloaderConfig') ? 'present' : 'not found'));

        $path = $args->getOption('name') ?? Configure::read('PreloaderConfig.name');
        $path = !empty($path) ? $path : ROOT . DS . 'preload.php';

        $result = $this->preloader->write($path);

        if ($result) {
            $io->hr();
            $io->success('Preload written to ' . $path);
            $io->out('You must restart your PHP service for the changes to take effect.');
            $io->hr();

            return static::CODE_SUCCESS;
        }

        $io->err('Error encountered writing to ' . $path);

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
        $packages = $args->getOption('packages') ?? Configure::read('PreloaderConfig.packages');
        if (empty($packages)) {
            return;
        }

        if (is_string($packages)) {
            $packages = explode(',', (string)$args->getOption('packages'));
        }

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
    private function plugins(Arguments $args)
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
                'help' => 'The preload file path (default: ROOT . DS . preload.php)',
            ])
            ->addOption('app', [
                'help' => 'Add your applications src directory into the preloader',
                'boolean' => true,
            ])
            ->addOption('plugins', [
                'help' => 'A comma separated list of your plugins to load or `*` to load all plugins/*',
            ])
            ->addOption('packages', [
                'help' => 'A comma separated list of packages (e.g. vendor-name/package-name) to add to the preloader',
            ]);

        if (defined('TEST')) {
            $parser->addOption('phpunit', [
                'help' => '(FOR TESTING ONLY)',
                'boolean' => true,
                'default' => false,
            ]);
        }

        return $parser;
    }
}
