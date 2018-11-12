<?php

namespace pdeans\Miva\Api;

use pdeans\Miva\Api\Exceptions\InvalidValueException;
use pdeans\Miva\Api\Exceptions\JsonSerializeException;
use stdClass;
use Tightenco\Collect\Support\Collection;

/**
 * API Response class
 */
class Response
{
    /**
     * API response body
     *
     * @var string
     */
    protected $body;

    /**
     * API errors object
     *
     * @var stdClass
     */
    protected $errors;

    /**
     * API Response array
     *
     * @var array
     */
    protected $functions;

    /**
     * API request function names list
     *
     * @var array
     */
    protected $functions_list;

    /**
     * Flag for determining if Api request was successful
     *
     * @var boolean
     */
    protected $success;

    /**
     * Construct API Response object
     *
     * @param array  $request_function_list API request function names list
     * @param string $response_body         API response body
     */
    public function __construct(array $request_function_list, string $response_body)
    {
        if (empty($request_function_list)) {
            throw new InvalidValueException('Empty request function list provided.');
        }

        $this->functions_list = $request_function_list;
        $this->body           = $response_body;
        $this->errors         = new stdClass;
        $this->functions      = [];

        $this->parseResponseBody($response_body);
    }

    /**
     * Get the response body
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Get API request errors
     *
     * @return \stdClass
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Get API request results for specific function
     *
     * @param string $function_name  The function name
     *
     * @return \Tightenco\Collect\Support\Collection
     */
    public function getFunction(string $function_name)
    {
        return (isset($this->functions[$function_name]) ? $this->functions[$function_name] : false);
    }

    /**
     * Get the API response object
     *
     * @param string|null $function_name Get API response for specific function
     *
     * @return array
     */
    public function getResponse($function_name = null)
    {
        if ($function_name !== null) {
            return $this->getFunction($function_name);
        }

        return $this->functions;
    }

    /**
     * Flag for determining if API request has errors
     *
     * @return boolean
     */
    public function isSuccess()
    {
        return $this->success;
    }

    /**
     * Parse the API request and set the API response
     *
     * @param string $response_body  The API response body
     */
    protected function parseResponseBody(string $response_body)
    {
        $resp = json_decode($response_body);

        if (json_last_error()) {
            throw new JsonSerializeException(json_last_error());
        }

        $functions = [];

        if (is_object($resp)) {
            $this->success = (bool)$resp->success;

            if ($this->success) {
                $functions = [$this->functions_list[0] => new Collection($resp)];
            } else {
                $this->errors->success = $this->success;
                $this->errors->code    = (string)$resp->error_code;
                $this->errors->message = (string)$resp->error_message;
            }
        } elseif (is_array($resp)) {
            foreach ($resp as $index => $results) {
                $function_name  = $this->functions_list[(count($this->functions_list) === 1 ? 0 : $index)];

                if (is_array($results)) {
                    foreach ($results as $result) {
                        $functions[$function_name][] = $result;
                    }
                } elseif (is_object($results)) {
                    $functions[$function_name][] = $results;
                }

                if (!empty($functions[$function_name])) {
                    $functions[$function_name] = new Collection($functions[$function_name]);
                }
            }
        }

        if ($this->success === null && empty(get_object_vars($this->errors))) {
            $this->success = true;
        }

        $this->functions = $functions;
    }
}
