<?php

namespace pdeans\Miva\Api;

use pdeans\Miva\Api\Exceptions\InvalidValueException;
use pdeans\Miva\Api\Exceptions\JsonSerializeException;
use stdClass;

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
     *
     * @throws \pdeans\Miva\Api\Exceptions\InvalidValueException
     */
    public function __construct(array $request_function_list, string $response_body)
    {
        if (empty($request_function_list)) {
            throw new InvalidValueException('Empty request function list provided.');
        }

        $this->functions_list = $request_function_list;
        $this->body           = $response_body;
        $this->errors         = new stdClass();
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
     * Get API response data property for specific function
     *
     * @param  string  $function_name The function name
     * @param  integer $index         The functions list function index
     *
     * @return \stdClass
     */
    public function getData(string $function_name, int $index = 0)
    {
        if (!$this->isValidFunction($function_name)) {
            $this->throwInvalidFunctionError($function_name);
        } elseif (!isset($this->functions[$function_name][$index])) {
            throw new InvalidValueException(
                'Index "' . $index . '" does not exist for function "' . $function_name . '".'
            );
        }

        $function_data = $this->functions[$function_name][$index];

        return ($function_data->data ?? $function_data);
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
     * Get full API response for specific function
     *
     * @param string $function_name  The function name
     *
     * @return array
     */
    public function getFunction(string $function_name)
    {
        if (!$this->isValidFunction($function_name)) {
            $this->throwInvalidFunctionError($function_name);
        }

        return $this->functions[$function_name];
    }

    /**
     * Get the API response functions list
     *
     * @return array
     */
    public function getFunctionsList()
    {
        return $this->functions_list;
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
     * Check if function name exists in the functions list
     *
     * @param  string  $function_name The function name
     *
     * @return boolean
     */
    protected function isValidFunction(string $function_name)
    {
        return isset($this->functions[$function_name]);
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
                $functions = [$this->functions_list[0] => [$resp]];
            } else {
                $this->errors->success = $this->success;
                $this->errors->code    = (string)$resp->error_code;
                $this->errors->message = (string)$resp->error_message;
            }
        } elseif (is_array($resp)) {
            $functions_list_count = count($this->functions_list);

            foreach ($resp as $index => $results) {
                $function_name  = $this->functions_list[($functions_list_count === 1 ? 0 : $index)];

                if (is_array($results)) {
                    foreach ($results as $result) {
                        $functions[$function_name][] = $result;
                    }
                } elseif (is_object($results)) {
                    $functions[$function_name][] = $results;
                }
            }
        }

        if ($this->success === null && empty(get_object_vars($this->errors))) {
            $this->success = true;
        }

        $this->functions = $functions;
    }

    /**
     * Throws an invalid function name error
     *
     * @param  string $function_name The function name
     *
     * @throws \pdeans\Miva\Api\Exceptions\InvalidValueException
     */
    protected function throwInvalidFunctionError(string $function_name)
    {
        throw new InvalidValueException('Function name "' . $function_name . '" invalid or missing from results list.');
    }
}
