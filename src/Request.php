<?php

namespace pdeans\Miva\Api;

use JsonException;
use pdeans\Http\Client;
use pdeans\Http\Request as HttpRequest;
use pdeans\Http\Response as HttpResponse;
use pdeans\Miva\Api\Builders\RequestBuilder;
use pdeans\Miva\Api\Exceptions\JsonSerializeException;

/**
 * API Request class
 */
class Request
{
    /**
     * API request body.
     *
     * @var string
     */
    protected string $body = '';

    /**
     * HTTP client (cURL) instance.
     *
     * @var \pdeans\Http\Client
     */
    protected Client $client;

    /**
     * API request headers.
     *
     * @var array
     */
    protected array $headers;

    /**
     * The HTTP request instance.
     *
     * @var \pdeans\Http\Request|null
     */
    protected HttpRequest|null $request = null;

    /**
     * The HTTP response instance.
     *
     * @var \pdeans\Http\Response|null
     */
    protected HttpResponse|null $response = null;

    /**
     * The API request builder instance.
     *
     * @var \pdeans\Miva\Api\Builders\RequestBuilder
     */
    protected RequestBuilder $requestBuilder;

    /**
     * Create a new API request instance.
     */
    public function __construct(RequestBuilder $requestBuilder, array $clientOpts = [])
    {
        $this->client = new Client($clientOpts);
        $this->headers = ['Content-Type' => 'application/json'];

        $this->setRequestBuilder($requestBuilder);
    }

    /**
     * Get the API request body.
     *
     * @link https://php.net/manual/en/json.constants.php
     *
     * @throws \pdeans\Miva\Api\Exceptions\JsonSerializeException
     */
    public function getBody(int $encodeOpts = JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT, int $depth = 512): string
    {
        try {
            $this->body = json_encode($this->requestBuilder, $encodeOpts, $depth);
        } catch (JsonException $exception) {
            throw new JsonSerializeException($exception->getMessage());
        }

        return $this->body;
    }

    /**
     * Get the request builder instance.
     */
    public function getRequestBuilder(): RequestBuilder
    {
        return $this->requestBuilder;
    }

    /**
     * Release the client request handler.
     */
    public function releaseClient(): void
    {
        $this->client->release();
    }

    /**
     * Get the API request.
     */
    public function request(): HttpRequest|null
    {
        return $this->request;
    }

    /**
     * Get the previous API response.
     */
    public function response(): HttpResponse|null
    {
        return $this->response;
    }

    /**
     * Send an API request.
     */
    public function sendRequest(string $url, Auth $auth, array $httpHeaders = []): HttpResponse
    {
        $this->response = null;

        $body = $this->getBody();

        $headers = array_merge(
            $this->headers,
            $httpHeaders,
            $auth->getAuthHeader($body)
        );

        $this->request = new HttpRequest($url, 'POST', $this->client->getStream($body), $headers);

        $this->response = $this->client->sendRequest($this->request);

        return $this->response;
    }

    /**
     * Set the request builder instance.
     */
    public function setRequestBuilder(RequestBuilder $requestBuilder): static
    {
        $this->requestBuilder = $requestBuilder;

        return $this;
    }
}
