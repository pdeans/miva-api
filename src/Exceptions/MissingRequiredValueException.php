<?php

namespace pdeans\Miva\Api\Exceptions;

use LogicException;
use pdeans\Miva\Api\Contracts\ExceptionInterface;

/**
 * MissingRequiredValueException class
 */
class MissingRequiredValueException extends LogicException implements ExceptionInterface
{
    /**
     * Construct MissingRequiredValueException object
     *
     * @param string          $message        The exception message
     * @param \Exception|null $last_exception The previous exception
     */
    public function __construct(string $message, Exception $last_exception = null)
    {
        parent::__construct($message, 0, $last_exception);
    }
}
