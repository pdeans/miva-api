<?php

namespace pdeans\Miva\Api\Exceptions;

use BadMethodCallException;
use Exception;

/**
 * InvalidMethodCallException class
 */
class InvalidMethodCallException extends BadMethodCallException
{
    /**
     * Construct InvalidMethodCallException object
     *
     * @param string          $message        The exception message
     * @param \Exception|null $last_exception The previous exception
     */
    public function __construct(string $message, Exception $last_exception = null)
    {
        parent::__construct($message, 0, $last_exception);
    }
}
