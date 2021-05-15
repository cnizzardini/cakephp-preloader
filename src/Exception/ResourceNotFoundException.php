<?php
declare(strict_types=1);

namespace CakePreloader\Exception;

use Cake\Core\Exception\CakeException;

/**
 * Thrown when a PreloadResource file cannot be found
 */
class ResourceNotFoundException extends CakeException
{
}
