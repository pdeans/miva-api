<?php

namespace pdeans\Miva\Api;

use pdeans\Miva\Api\Builders\FunctionBuilder;
use pdeans\Miva\Api\Builders\RequestBuilder;
use pdeans\Miva\Api\Exceptions\InvalidMethodCallException;
use pdeans\Miva\Api\Exceptions\MissingRequiredValueException;
use pdeans\Miva\Api\Request as ApiRequest;
use pdeans\Miva\Api\Response as ApiResponse;

/**
 * pdeans\Miva\ApiManager
 *
 * This is the primary class to instantiate, configure, and interact with
 * the API.
 */
class Manager
{
    /**
     * Api Auth
     *
     * @var \pdeans\Miva\Api\Auth
     */
    protected $auth;

    /**
     * Http headers
     *
     * @var array
     */
    protected $headers;

    /**
     * PSR-7 Request
     *
     * @var \Zend\Diactoros\Request
     */
    protected $last_request;

    /**
     * PSR-7 Response
     *
     * @var \Zend\Diactoros\Response
     */
    protected $last_response;

    /**
     * Api configuration options
     *
     * @var array
     */
    protected $options;

    /**
     * Api RequestBuilder
     *
     * @var \pdeans\Miva\Api\Builders\RequestBuilder
     */
    protected $request;

    /**
     * Api endpoint
     *
     * @var string
     */
    protected $url;

    /**
     * Construct a Manager object
     *
     * @param array $options  Api configuration options
     */
    public function __construct(array $options)
    {
        if (empty($options['url'])) {
            throw new MissingRequiredValueException('Missing required option "url".');
        }

        if (empty($options['access_token'])) {
            throw new MissingRequiredValueException('Missing required option "access_token".');
        }

        if (!isset($options['private_key'])) {
            throw new MissingRequiredValueException(
                'Missing required option "private_key". Hint: Set the option value
                to an empty string if accepting requests without a signature.'
            );
        }

        if (empty($options['store_code'])) {
            throw new MissingRequiredValueException('Missing required option "store_code".');
        }

        $this->options      = $options;
        $this->last_request = null;

        $this->auth = new Auth(
            (string)$this->options['access_token'],
            (string)$this->options['private_key'],
            (isset($this->options['hmac']) ? (string)$this->options['hmac'] : 'sha256')
        );

        $this->createRequest();
        $this->setUrl($this->options['url']);

        $this->headers = [];

        if (!empty($this->options['http_headers'])) {
            $this->addHeaders($this->options['http_headers']);
        }
    }

    /**
     * Add API function to request function list
     *
     * @param \pdeans\Miva\Api\Builders\FunctionBuilder|null $function
     *
     * @return self
     */
    public function add(FunctionBuilder $function = null)
    {
        if ($function !== null) {
            $this->request->function = $function;
        }

        $this->request->addFunction();

        return $this;
    }

    /**
     * Add HTTP request header
     *
     * @param string $header_name  The header name
     * @param string $header_value The header value
     *
     * @return self
     */
    public function addHeader(string $header_name, string $header_value)
    {
        $this->headers[$header_name] = $header_value;

        return $this;
    }

    /**
     * Add list of HTTP request headers
     *
     * @param array $headers  List of headers in key/value form
     *
     * @return self
     */
    public function addHeaders(array $headers)
    {
        foreach ($headers as $header_name => $header_value) {
            $this->addHeader($header_name, $header_value);
        }

        return $this;
    }

    /**
     * Clear the current \pdeans\Miva\Api\Builders\RequestBuilder instance
     *
     * @return self
     */
    protected function clearRequest()
    {
        $this->request = null;

        return $this;
    }

    /**
     * Create a new \pdeans\Miva\Api\Builders\RequestBuilder instance
     *
     * @return self
     */
    protected function createRequest()
    {
        $this->request = new RequestBuilder(
            (string)$this->options['store_code'],
            (isset($this->options['timestamp']) ? (bool)$this->options['timestamp'] : true)
        );

        return $this;
    }

    /**
     * Create a new API function
     *
     * @param string $name  The API function name
     *
     * @return self
     */
    public function func(string $name)
    {
        $this->request->newFunction($name);

        return $this;
    }

    /**
     * Get API request function list
     *
     * @return array
     */
    public function getFunctionList()
    {
        return $this->request->getFunctionList();
    }

    /**
     * Get the list of HTTP request headers
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Get the last PSR-7 Request object
     *
     * @return \Zend\Diactoros\Request
     */
    public function getLastRequest()
    {
        return $this->last_request;
    }

    /**
     * Get the last PSR-7 Response object
     *
     * @return \Zend\Diactoros\Response
     */
    public function getLastResponse()
    {
        return $this->last_response;
    }

    /**
     * Get API request body
     *
     * @link http://php.net/manual/en/json.constants.php
     *
     * @param int $encode_opts Bitmask consisting of JSON constants
     * @param int $depth       The maximum JSON depth
     *
     * @return string API request body (JSON)
     */
    public function getRequestBody(int $encode_opts = 128, int $depth = 512)
    {
        return (new ApiRequest($this->request))->getBody($encode_opts, $depth);
    }

    /**
     * Get the API endpoint url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Send API request
     *
     * @param boolean $raw_response  Return raw response body
     *
     * @return string|\pdeans\Miva\Api\Response  Raw response body|\pdeans\Miva\Api\Response object
     */
    public function send($raw_response = false)
    {
        $request = new ApiRequest(
            $this->request,
            (isset($this->options['http_client']) ? (array)$this->options['http_client'] : [])
        );

        $response = $request->sendRequest($this->getUrl(), $this->auth, $this->getHeaders());

        $this->last_request  = $request->getLastRequest();
        $this->last_response = $response;

        // Save the function list names before clearing the request builder
        $function_list = array_keys($this->getFunctionList());

        // Refresh request builder
        $this->clearRequest();
        $this->createRequest();

        if ($raw_response) {
            return (string)$response->getBody();
        }

        return new ApiResponse($function_list, (string)$response->getBody());
    }

    /**
     * Set the API endpoint url
     *
     * @param string $url  The API endpoint url
     *
     * @return self
     */
    public function setUrl(string $url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Invoke \pdeans\Miva\Api\Builders\FunctionBuilder helper methods
     *
     * @param string $method    The method name
     * @param array  $arguments The method arguments
     *
     * @return self
     */
    public function __call($method, $arguments)
    {
        if (class_exists(FunctionBuilder::class) && in_array($method, get_class_methods(FunctionBuilder::class))) {
            $this->request->function->{$method}(...$arguments);

            return $this;
        }

        throw new InvalidMethodCallException('Bad method call "' . $method . '".');
    }
}
