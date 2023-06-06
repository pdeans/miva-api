<?php

namespace pdeans\Miva\Api;

use JsonException;
use pdeans\Http\Client;
use pdeans\Http\Factories\StreamFactory;
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
    protected string $body;

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
     * The previous API request instance.
     *
     * @var \pdeans\Http\Request|null
     */
    protected HttpRequest|null $prevRequest;

    /**
     * The API request builder instance.
     *
     * @var \pdeans\Miva\Api\Builders\RequestBuilder
     */
    protected RequestBuilder $request;

    /**
     * PSR-7 stream factory instance.
     *
     * @var \pdeans\Http\Factories\StreamFactory
     */
    protected StreamFactory $streamFactory;

    /**
     * Create a new API request instance.
     */
    public function __construct(RequestBuilder $request, array $clientOpts = [])
    {
        $this->request = $request;
        $this->client = new Client($clientOpts);
        $this->headers = ['Content-Type' => 'application/json'];
        $this->body = '';
        $this->prevRequest = null;
        $this->streamFactory = new StreamFactory();
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
            $this->body = json_encode($this->request, $encodeOpts, $depth);
        } catch (JsonException $exception) {
            throw new JsonSerializeException($exception->getMessage());
        }

        return $this->body;
    }

    /**
     * Get the previous API request.
     */
    public function getPreviousRequest(): HttpRequest|null
    {
        return $this->prevRequest;
    }

    /**
     * Send an API request.
     */
    public function sendRequest(string $url, Auth $auth, array $httpHeaders = []): HttpResponse
    {
        $body = $this->getBody();

        $headers = array_merge(
            $this->headers,
            $httpHeaders,
            $auth->getAuthHeader($body)
        );

        $request = new HttpRequest($url, 'POST', $this->streamFactory->createStream($body), $headers);

        $this->prevRequest = $request;

        return $this->client->sendRequest($request);
    }
}
