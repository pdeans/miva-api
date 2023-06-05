<?php

namespace pdeans\Miva\Api;

use pdeans\Http\Request;
use pdeans\Http\Response;
use pdeans\Miva\Api\Request as ApiRequest;
use pdeans\Miva\Api\Builders\RequestBuilder;
use pdeans\Miva\Api\Response as ApiResponse;
use pdeans\Miva\Api\Builders\FunctionBuilder;
use pdeans\Miva\Api\Exceptions\InvalidMethodCallException;
use pdeans\Miva\Api\Exceptions\MissingRequiredValueException;

/**
 * This is the client class to interact with the Miva JSON API.
 */
class Client
{
    /**
     * Api Auth instance.
     *
     * @var \pdeans\Miva\Api\Auth
     */
    protected Auth $auth;

    /**
     * Http headers.
     *
     * @var array
     */
    protected array $headers;

    /**
     * PSR-7 Request instance.
     *
     * @var \pdeans\Http\Request
     */
    protected Request $lastRequest;

    /**
     * PSR-7 Response instance.
     *
     * @var \pdeans\Http\Response
     */
    protected Response $lastResponse;

    /**
     * Api configuration options.
     *
     * @var array
     */
    protected array $options;

    /**
     * Api RequestBuilder instance.
     *
     * @var \pdeans\Miva\Api\Builders\RequestBuilder
     */
    protected RequestBuilder $request;

    /**
     * Miva JSON API endpoint value.
     *
     * @var string
     */
    protected string $url;

    /**
     * Create a new client instance.
     */
    public function __construct(array $options)
    {
        $this->setOptions($options);
        $this->lastRequest = null;

        $this->auth = new Auth(
            (string) $this->options['access_token'],
            (string) $this->options['private_key'],
            isset($this->options['hmac']) ? (string) $this->options['hmac'] : 'sha256'
        );

        $this->createRequest();
        $this->setUrl($this->options['url']);

        $this->headers = [];

        if (!empty($this->options['http_headers'])) {
            $this->addHeaders($this->options['http_headers']);
        }
    }

    /**
     * Add API function to request function list.
     */
    public function add(FunctionBuilder|null $function = null): self
    {
        if (! is_null($function)) {
            $this->request->function = $function;
        }

        $this->request->addFunction();

        return $this;
    }

    /**
     * Add HTTP request header.
     */
    public function addHeader(string $headerName, string $headerValue): self
    {
        $this->headers[$headerName] = $headerValue;

        return $this;
    }

    /**
     * Add list of HTTP request headers.
     */
    public function addHeaders(array $headers): self
    {
        foreach ($headers as $headerName => $headerValue) {
            $this->addHeader($headerName, $headerValue);
        }

        return $this;
    }

    /**
     * Clear the current request builder instance.
     */
    protected function clearRequest(): self
    {
        $this->request = null;

        return $this;
    }

    /**
     * Create a new request builder instance.
     */
    protected function createRequest(): self
    {
        $this->request = new RequestBuilder(
            (string) $this->options['store_code'],
            isset($this->options['timestamp']) ? (bool) $this->options['timestamp'] : true
        );

        return $this;
    }

    /**
     * Create a new API function.
     */
    public function func(string $name): self
    {
        $this->request->newFunction($name);

        return $this;
    }

    /**
     * Get the API request function list.
     */
    public function getFunctionList(): array
    {
        return $this->request->getFunctionList();
    }

    /**
     * Get the list of API request headers.
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get the last request instance.
     */
    public function getLastRequest(): Request
    {
        return $this->lastRequest;
    }

    /**
     * Get the last response instance.
     */
    public function getLastResponse(): Response
    {
        return $this->lastResponse;
    }

    /**
     * Get the API client options.
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Get API request body.
     *
     * @link https://php.net/manual/en/json.constants.php Available options for the $encodeOpts parameter.
     */
    public function getRequestBody(int $encodeOpts = 128, int $depth = 512): string
    {
        return (new ApiRequest($this->request))->getBody($encodeOpts, $depth);
    }

    /**
     * Get the API endpoint url.
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Send the API request.
     */
    public function send(bool $rawResponse = false): string|Response
    {
        $request = new ApiRequest(
            $this->request,
            (isset($this->options['http_client']) ? (array)$this->options['http_client'] : [])
        );

        $response = $request->sendRequest($this->getUrl(), $this->auth, $this->getHeaders());

        $this->lastRequest  = $request->getLastRequest();
        $this->lastResponse = $response;

        // Save the function list names before clearing the request builder
        $functionList = array_keys($this->getFunctionList());

        // Refresh request builder
        $this->clearRequest();
        $this->createRequest();

        $responseBody = (string) $response->getBody();

        return $rawResponse ? $responseBody : new ApiResponse($functionList, $responseBody);
    }

    /**
     * Set the API client options.
     */
    public function setOptions(array $options): self
    {
        $this->options = $this->validateOptions($options);

        return $this;
    }

    /**
     * Set the API endpoint url.
     */
    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Validate the client configuration options.
     *
     * @throws \pdeans\Miva\Api\Exceptions\MissingRequiredValueException
     */
    protected function validateOptions(array $options): array
    {
        if (!isset($options['private_key'])) {
            throw new MissingRequiredValueException(
                'Missing required option "private_key". Hint: Set the option value
                to an empty string if accepting requests without a signature.'
            );
        }

        $requiredValueOptions = [
            'access_token',
            'private_key',
            'store_code',
            'url',
        ];

        foreach ($requiredValueOptions as $option) {
            if (empty($options[$option])) {
                throw new MissingRequiredValueException('Missing required option "' . $option . '".');
            }
        }

        return $options;
    }

    /**
     * Invoke \pdeans\Miva\Api\Builders\FunctionBuilder helper methods.
     */
    public function __call(string $method, array $arguments): self
    {
        if (class_exists(FunctionBuilder::class) && in_array($method, get_class_methods(FunctionBuilder::class))) {
            $this->request->function->{$method}(...$arguments);

            return $this;
        }

        throw new InvalidMethodCallException('Bad method call "' . $method . '".');
    }
}
