<?php

namespace pdeans\Miva\Api\Exceptions;

use Exception;

/**
 * BaseException class
 */
class BaseException extends Exception
{
    /**
     * Construct BaseException object
     *
     * @param string          $message        The exception message
     * @param \Exception|null $last_exception The previous exception
     */
    public function __construct(string $message, Exception $last_exception = null)
    {
        parent::__construct($message, 0, $last_exception);
    }
}
