<?php
declare(strict_types=1);

namespace CakePreloader;

use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\Filesystem\Filesystem;
use Cake\I18n\FrozenTime;
use Cake\Utility\Inflector;
use CakePreloader\Exception\ResourceNotFoundException;
use RuntimeException;
use SplFileInfo;

/**
 * Writes a preload file
 *
 * @see https://www.php.net/manual/en/opcache.preloading.php
 */
class Preloader
{
    /**
     * An array of PreloadResource instances
     *
     * @var \CakePreloader\PreloadResource[]
     */
    private array $preloadResources = [];

    /**
     * Returns an array of PreloadResource after sorting alphabetically
     *
     * @return \CakePreloader\PreloadResource[]
     */
    public function getPreloadResources(): array
    {
        uasort($this->preloadResources, function (PreloadResource $a, PreloadResource $b) {
            return strcasecmp($a->getFile(), $b->getFile());
        });

        return $this->preloadResources;
    }

    /**
     * Sets preloadResources
     *
     * @param \CakePreloader\PreloadResource[] $preloadResources the array of PreloadResource instances
     * @return $this
     */
    public function setPreloadResources(array $preloadResources)
    {
        $this->preloadResources = $preloadResources;

        return $this;
    }

    /**
     * Loads files in the file system $path recursively as PreloadResources after applying the optional callback
     *
     * @param string $path The file system path
     * @param callable|null $callback An optional callback which receives SplFileInfo as an argument
     * @return $this
     */
    public function loadPath(string $path, $callback = null)
    {
        $iterator = (new Filesystem())->findRecursive(
            $path,
            function (SplFileInfo $file) use ($callback) {
                if ($file->getExtension() !== 'php') {
                    return false;
                }

                return is_callable($callback) ? $callback($file) : true;
            }
        );

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if (Inflector::camelize($file->getFilename()) === $file->getFilename()) {
                $this->preloadResources[] = new PreloadResource('require_once', $file->getPathname());
                continue;
            }

            $this->preloadResources[] = new PreloadResource('opcache_compile_file', $file->getPathname());
        }

        return $this;
    }

    /**
     * Write preloader to the specified path
     *
     * @param string $path Default file path is ROOT . 'preload.php'
     * @return bool
     * @throws \RuntimeException
     */
    public function write(string $path = ROOT . DS . 'preload.php'): bool
    {
        if ((file_exists($path) && !is_writable($path))) {
            throw new RuntimeException('File path is not writable: ' . $path);
        }

        EventManager::instance()->dispatch(new Event('CakePreloader.beforeWrite', $this));

        return (bool)file_put_contents($path, $this->contents());
    }

    /**
     * Returns a string to be written to the preload file
     *
     * @return string
     * @throws \RuntimeException
     */
    private function contents(): string
    {
        ob_start();

        $title = sprintf("# Preload Generated at %s \n", FrozenTime::now());

        echo "<?php\n\n";
        echo "$title \n";
        echo "if (in_array(PHP_SAPI, ['cli', 'phpdbg'], true)) {\n";
        echo "\treturn;\n";
        echo "}\n\n";
        echo "require_once('" . ROOT . DS . 'vendor' . DS . 'autoload.php' . "'); \n";

        $scripts = [];

        foreach ($this->getPreloadResources() as $resource) {
            try {
                if ($resource->getType() === 'require_once') {
                    echo $resource->getResource();
                    continue;
                }
                $scripts[] = $resource->getResource();
            } catch (ResourceNotFoundException $e) {
                triggerWarning('Preloader skipped the following: ' . $e->getMessage());
            }
        }

        if (!empty($scripts)) {
            echo "# Scripts \n";
            echo implode('', $scripts ?? []);
        }

        $content = ob_get_contents();
        ob_end_clean();

        if (!is_string($content)) {
            throw new RuntimeException('Unable to generate contents for preload');
        }

        return $content;
    }
}
