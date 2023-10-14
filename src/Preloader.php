<?php
declare(strict_types=1);

namespace CakePreloader;

use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\I18n\DateTime;
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
     * @var bool Should the preload file continue when run via php-cli?
     */
    private bool $allowCli = false;

    /**
     * An array of PreloadResource instances
     *
     * @var array<\CakePreloader\PreloadResource>
     */
    private array $preloadResources = [];

    /**
     * Returns an array of PreloadResource after sorting alphabetically
     *
     * @return array<\CakePreloader\PreloadResource>
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
     * @param array<\CakePreloader\PreloadResource> $preloadResources the array of PreloadResource instances
     * @return $this
     */
    public function setPreloadResources(array $preloadResources)
    {
        $this->preloadResources = $preloadResources;

        return $this;
    }

    /**
     * Loads files in the file system $path recursively as PreloadResources after applying the optional callback. Note,
     * loading script files has been disabled by the library in CakePHP 5.
     *
     * @param string $path The file system path
     * @param callable|null $callback An optional callback which receives SplFileInfo as an argument
     * @return $this
     */
    public function loadPath(string $path, ?callable $callback = null)
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
            $result = $this->isClass($file);
            if ($result === true) {
                $this->preloadResources[] = new PreloadResource('require_once', $file->getPathname());
            }
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
     * @param bool $bool Should the preload file continue when run via php-cli?
     * @return $this
     */
    public function allowCli(bool $bool)
    {
        $this->allowCli = $bool;

        return $this;
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

        $title = sprintf("# Preload Generated at %s \n", DateTime::now());

        if ($this->allowCli) {
            $ignores = "['phpdbg']";
        } else {
            $ignores = "['cli', 'phpdbg']";
        }

        echo "<?php\n\n";
        echo "$title \n";
        echo "if (in_array(PHP_SAPI, $ignores, true)) {\n";
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
            echo implode('', $scripts);
        }

        $content = ob_get_contents();
        ob_end_clean();

        if (!is_string($content)) {
            throw new RuntimeException('Unable to generate contents for preload');
        }

        return $content;
    }

    /**
     * Returns false if the file name is not PSR-4, true if it as and is a class, null otherwise.
     *
     * @param \SplFileInfo $file Instance of SplFileInfo
     * @return bool|null
     */
    private function isClass(SplFileInfo $file): ?bool
    {
        if (Inflector::camelize($file->getFilename()) !== $file->getFilename()) {
            return false;
        }

        $contents = file_get_contents($file->getPathname());
        if (!$contents) {
            return null;
        }

        $className = str_replace('.php', '', $file->getFilename());
        if (strstr($contents, "class $className") && strstr($contents, 'namespace')) {
            return true;
        }

        return null;
    }
}
