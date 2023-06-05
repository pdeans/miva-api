<?php

namespace pdeans\Miva\Api\Exceptions;

use Exception;
use RuntimeException;

/**
 * JsonSerializeException class
 */
class JsonSerializeException extends RuntimeException
{
    /**
     * Construct JsonSerializeException object
     *
     * @link http://php.net/manual/en/function.json-last-error.php
     *
     * @param int             $json_last_error The JSON error constant
     * @param \Exception|null $last_exception  The previous exception
     */
    public function __construct(int $json_last_error, Exception $last_exception = null)
    {
        $msg = '';

        switch ($json_last_error) {
            case JSON_ERROR_NONE:
                $msg = 'No error has occurred.';
                break;
            case JSON_ERROR_DEPTH:
                $msg = 'The maximum stack depth has been exceeded.';
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $msg = 'Invalid or malformed JSON.';
                break;
            case JSON_ERROR_CTRL_CHAR:
                $msg = 'Control character error, possibly incorrectly encoded.';
                break;
            case JSON_ERROR_SYNTAX:
                $msg = 'Syntax error.';
                break;
            case JSON_ERROR_UTF8:
                $msg = 'Malformed UTF-8 characters, possibly incorrectly encoded.';
                break;
            case JSON_ERROR_RECURSION:
                $msg = 'One or more recursive references in the value to be encoded.';
                break;
            case JSON_ERROR_INF_OR_NAN:
                $msg = 'One or more NAN or INF values in the value to be encoded.';
                break;
            case JSON_ERROR_UNSUPPORTED_TYPE:
                $msg = 'A value of a type that cannot be encoded was given.';
                break;
            case JSON_ERROR_INVALID_PROPERTY_NAME:
                $msg = 'A property name that cannot be encoded was given.';
                break;
            case JSON_ERROR_UTF16:
                $msg = 'Malformed UTF-16 characters, possibly incorrectly encoded.';
                break;
            default:
                $msg = 'Unknown parse error.';
                break;
        }

        parent::__construct($msg, 0, $last_exception);
    }
}
