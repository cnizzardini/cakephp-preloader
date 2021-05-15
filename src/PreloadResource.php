<?php
declare(strict_types=1);

namespace CakePreloader;

use CakePreloader\Exception\ResourceNotFoundException;
use InvalidArgumentException;

/**
 * Stores a file that will be written to the preload file as a require_once or opcache_compile_file call.
 */
class PreloadResource
{
    /**
     * Preload types.
     *
     * require_once will load additional dependencies in the file, opcache_compile_file will only load the file. The
     * later may lead to the file being unusable by opcache.preloading if not all the dependencies have been preloaded.
     *
     * @see https://www.php.net/manual/en/opcache.preloading.php
     * @var array
     */
    private const TYPES = [
        'require_once',
        'opcache_compile_file',
    ];

    /**
     * How to preload the file. See PreloadResource::TYPES
     *
     * @var string
     */
    private string $type;

    /**
     * The absolute file path to be preloaded
     *
     * @var string
     */
    private string $file;

    /**
     * @param string $type The preload resource type, see PreloadResource::TYPES
     * @param string $file The preload file path
     */
    public function __construct(string $type, string $file)
    {
        if (!in_array($type, self::TYPES)) {
            throw new InvalidArgumentException(
                'Argument must be on of ' . implode(', ', self::TYPES)
            );
        }

        $this->type = $type;
        $this->file = $file;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getFile(): string
    {
        return $this->file;
    }

    /**
     * Returns the resource to be preloaded as either a require_once or opcache_compile_file string
     *
     * @return string
     * @throws \CakePreloader\Exception\ResourceNotFoundException
     */
    public function getResource(): string
    {
        if (!file_exists($this->file)) {
            throw new ResourceNotFoundException(
                'File `' . $this->file . '` does not exist'
            );
        }

        return $this->type . "('" . $this->file . "'); \n";
    }
}
