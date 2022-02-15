<?php
declare(strict_types=1);

namespace CakePreloader\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use CakePreloader\Exception\PreloadWriteException;
use CakePreloader\PreloaderService;

/**
 * Generates a preload file
 *
 * This can be included as part of your build process.
 *
 * @see https://www.php.net/manual/en/opcache.preloading.php
 */
class PreloaderCommand extends Command
{
    private PreloaderService $preloaderService;

    /**
     * @param \CakePreloader\PreloaderService $preloaderService PreloaderService
     */
    public function __construct(PreloaderService $preloaderService)
    {
        parent::__construct();
        $this->preloaderService = $preloaderService;
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
        $io->hr();
        $io->out('Generating preloader...');
        $io->hr();

        $io->info('Preloader Config: ' . (Configure::check('PreloaderConfig') ? 'present' : 'not found'));

        try {
            $path = $this->preloaderService->generate($args, $io);
            $io->hr();
            $io->success('Preload written to ' . $path);
            $io->out('You must restart your PHP service for the changes to take effect.');
            $io->hr();

            return static::CODE_SUCCESS;
        } catch (PreloadWriteException $e) {
            $io->err($e->getMessage());

            return static::CODE_ERROR;
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
            ])
            ->addOption('cli', [
                'help' => 'Should the preloader file load when run via php-cli?',
                'boolean' => true,
                'default' => false,
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
