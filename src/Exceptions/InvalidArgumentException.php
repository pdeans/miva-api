<?php

namespace pdeans\Miva\Api\Exceptions;

use Exception;
use InvalidArgumentException as SPLInvalidArgumentException;

/**
 * InvalidArgumentException class
 */
class InvalidArgumentException extends SPLInvalidArgumentException
{
    /**
     * Construct InvalidArgumentException object
     *
     * @param string          $message        The exception message
     * @param \Exception|null $last_exception The previous exception
     */
    public function __construct(string $message, Exception $last_exception = null)
    {
        parent::__construct($message, 0, $last_exception);
    }
}
