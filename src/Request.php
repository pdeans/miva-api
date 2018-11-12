<?php

namespace pdeans\Miva\Api;

use pdeans\Http\Client;
use pdeans\Http\Factories\MessageFactory;
use pdeans\Miva\Api\Builders\RequestBuilder;
use pdeans\Miva\Api\Exceptions\JsonSerializeException;

/**
 * API Request class
 */
class Request
{
    /**
     * API request body
     *
     * @var string
     */
    protected $body;

    /**
     * HTTP Client (cURL)
     *
     * @var \pdeans\Http\Client
     */
    protected $client;

    /**
     * API request headers
     *
     * @var array
     */
    protected $headers;

    /**
     * The last API request
     *
     * @var \Zend\Diactoros\Request
     */
    protected $last_request;

    /**
     * Factory for create PSR-7 requests
     *
     * @var \pdeans\Http\Factories\MessageFactory
     */
    protected $msg_factory;

    /**
     * The API RequestBuilder instance
     *
     * @var \pdeans\Miva\Api\Builders\RequestBuilder
     */
    protected $request;

    /**
     * Construct API Request object
     *
     * @param \pdeans\Miva\Api\Builders\RequestBuilder $request     RequestBuilder object
     * @param array                                    $client_opts cURL client options
     */
    public function __construct(RequestBuilder $request, array $client_opts = [])
    {
        $this->request      = $request;
        $this->client       = new Client($client_opts);
        $this->msg_factory  = new MessageFactory;
        $this->headers      = ['Content-Type' => 'application/json'];
        $this->body         = null;
        $this->last_request = null;
    }

    /**
     * Create the request body
     *
     * @link http://php.net/manual/en/json.constants.php
     *
     * @param \pdeans\Miva\Api\Builders\RequestBuilder $request     The RequestBuilder object
     * @param int                                      $encode_opts Bitmask consisting of JSON constants
     * @param int                                      $depth       The maximum JSON depth
     *
     * @return string
     */
    protected function createRequestBody(RequestBuilder $request, int $encode_opts = 0, int $depth = 512)
    {
        $request_body = json_encode($request, $encode_opts, $depth);

        if (json_last_error()) {
            throw new JsonSerializeException(json_last_error());
        }

        return $request_body;
    }

    /**
     * Get the request body
     *
     * @link http://php.net/manual/en/json.constants.php
     *
     * @param int $encode_opts Bitmask consisting of JSON constants
     * @param int $depth       The maximum JSON depth
     *
     * @return string
     */
    public function getBody(int $encode_opts = 128, int $depth = 512)
    {
        $this->body = $this->createRequestBody($this->request, $encode_opts, $depth);

        return $this->body;
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
     * Send an API request
     *
     * @param string $url          The API endpoint url
     * @param Auth   $auth         API Auth object
     * @param array  $http_headers List of HTTP headers
     *
     * @return \Zend\Diactoros\Response
     */
    public function sendRequest(string $url, Auth $auth, array $http_headers = [])
    {
        $body = $this->getBody();
        $headers = array_merge(
            $this->headers,
            $http_headers,
            $auth->getAuthHeader($body)
        );

        $request = $this->msg_factory->createRequest('POST', $url, $headers, $body);

        $this->last_request = $request;

        return $this->client->sendRequest($request);
    }
}
