<?php

namespace pdeans\Miva\Api;

use pdeans\Http\Request as HttpRequest;
use pdeans\Http\Response as HttpResponse;
use pdeans\Miva\Api\Request;
use pdeans\Miva\Api\Response;
use pdeans\Miva\Api\Builders\FunctionBuilder;
use pdeans\Miva\Api\Builders\RequestBuilder;
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
     * List of API HTTP request headers.
     *
     * @var array
     */
    protected array $headers;

    /**
     * Api configuration options.
     *
     * @var array
     */
    protected array $options;

    /**
     * Api Request instance.
     *
     * @var \pdeans\Miva\Api\Request|null
     */
    protected Request|null $request;

    /**
     * Api RequestBuilder instance.
     *
     * @var \pdeans\Miva\Api\Builders\RequestBuilder|null
     */
    protected RequestBuilder|null $requestBuilder;

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

        $this->auth = new Auth(
            (string) $this->options['access_token'],
            (string) $this->options['private_key'],
            isset($this->options['hmac']) ? (string) $this->options['hmac'] : 'sha256'
        );

        $this->request = null;

        $this->createRequestBuilder();
        $this->setUrl($this->options['url']);

        $this->headers = [];

        if (! empty($this->options['http_headers'])) {
            $this->addHeaders($this->options['http_headers']);
        }
    }

    /**
     * Add API function to request function list.
     */
    public function add(FunctionBuilder|null $function = null): static
    {
        if (! is_null($function)) {
            $this->requestBuilder->function = $function;
        }

        $this->requestBuilder->addFunction();

        return $this;
    }

    /**
     * Add HTTP request header.
     */
    public function addHeader(string $headerName, string $headerValue): static
    {
        $this->headers[$headerName] = $headerValue;

        return $this;
    }

    /**
     * Add list of HTTP request headers.
     */
    public function addHeaders(array $headers): static
    {
        foreach ($headers as $headerName => $headerValue) {
            $this->addHeader($headerName, $headerValue);
        }

        return $this;
    }

    /**
     * Clear the current request builder instance.
     */
    protected function clearRequestBuilder(): static
    {
        $this->requestBuilder = null;

        return $this;
    }

    /**
     * Create a new request builder instance.
     */
    protected function createRequestBuilder(): static
    {
        $this->requestBuilder = new RequestBuilder(
            (string) $this->options['store_code'],
            isset($this->options['timestamp']) ? (bool) $this->options['timestamp'] : true
        );

        return $this;
    }

    /**
     * Create a new API function.
     */
    public function func(string $name): static
    {
        $this->requestBuilder->newFunction($name);

        return $this;
    }

    /**
     * Get the API request function list.
     */
    public function getFunctionList(): array
    {
        return $this->requestBuilder->getFunctionList();
    }

    /**
     * Get the list of API request headers.
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get the previous request instance.
     */
    public function getPreviousRequest(): HttpRequest|null
    {
        return $this->request?->request();
    }

    /**
     * Get the previous response instance.
     */
    public function getPreviousResponse(): HttpResponse|null
    {
        return $this->request?->response();
    }

    /**
     * Get the API client options.
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Get the API Request instance.
     */
    public function getRequest(): Request
    {
        if (! $this->request instanceof Request) {
            $this->request = new Request(
                $this->requestBuilder,
                isset($this->options['http_client']) ? (array) $this->options['http_client'] : []
            );
        }

        return $this->request;
    }

    /**
     * Get API request body.
     *
     * @link https://php.net/manual/en/json.constants.php Available options for the $encodeOpts parameter.
     */
    public function getRequestBody(int $encodeOpts = JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT, int $depth = 512): string
    {
        return $this->getRequest()
            ->getBody($encodeOpts, $depth);
    }

    /**
     * Get the API endpoint url.
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Refresh the request builder instance.
     */
    protected function refreshRequestBuilder(): static
    {
        $this->clearRequestBuilder();
        $this->createRequestBuilder();

        if ($this->request instanceof Request) {
            $this->request->setRequestBuilder($this->requestBuilder);
        }

        return $this;
    }

    /**
     * Send the API request.
     */
    public function send(bool $rawResponse = false): string|Response
    {
        $request = $this->getRequest();

        $response = $request->sendRequest($this->getUrl(), $this->auth, $this->getHeaders());

        // Save the function list names before clearing the request builder
        $functionList = array_keys($this->getFunctionList());

        // Refresh request builder
        $this->refreshRequestBuilder();

        $responseBody = (string) $response->getBody();

        return $rawResponse ? $responseBody : new Response($functionList, $responseBody);
    }

    /**
     * Set the API client options.
     */
    public function setOptions(array $options): static
    {
        $this->options = $this->validateOptions($options);

        return $this;
    }

    /**
     * Set the API endpoint url.
     */
    public function setUrl(string $url): static
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
    public function __call(string $method, array $arguments): static
    {
        if (class_exists(FunctionBuilder::class) && in_array($method, get_class_methods(FunctionBuilder::class))) {
            $this->requestBuilder->function->{$method}(...$arguments);

            return $this;
        }

        throw new InvalidMethodCallException('Bad method call "' . $method . '".');
    }
}
