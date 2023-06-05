<?php

namespace pdeans\Miva\Api\Builders;

use pdeans\Miva\Api\Builders\FunctionBuilder;
use pdeans\Miva\Api\Contracts\BuilderInterface;
use pdeans\Miva\Api\Exceptions\MissingRequiredValueException;

/**
 * RequestBuilder class
 */
class RequestBuilder implements BuilderInterface
{
    /**
     * Function builder instance.
     *
     * @var \pdeans\Miva\Api\Builders\FunctionBuilder|null
     */
    public FunctionBuilder|null $function;

    /**
     * API request function list.
     *
     * @var array
     */
    protected array $functionList;

    /**
     * Miva store code.
     *
     * @var string
     */
    public string $storeCode;

    /**
     * Flag to determine if the Miva_Request_Timestamp parameter should be added to the request.
     *
     * @var bool
     */
    public bool $addTimestamp;

    /**
     * Create a new request builder instance.
     *
     * @throws \pdeans\Miva\Api\Exceptions\MissingRequiredValueException
     */
    public function __construct(string $storeCode, bool $addTimestamp = true)
    {
        $this->storeCode = trim($storeCode);

        if ($this->storeCode === '') {
            throw new MissingRequiredValueException('A valid store code value must be provided.');
        }

        $this->function = null;
        $this->functionList = [];
        $this->addTimestamp = $addTimestamp;
    }

    /**
     * Add the current FunctionBuilder instance to the API request function list.
     */
    public function addFunction(): self
    {
        $this->functionList[$this->function->name][] = $this->function;

        return $this;
    }

    /**
     * Get the API request function list.
     */
    public function getFunctionList(): array
    {
        return $this->functionList;
    }

    /**
     * Define JSON serialization format.
     *
     * @throws \pdeans\Miva\Api\Exceptions\MissingRequiredValueException
     */
    public function jsonSerialize(): array
    {
        if (empty($this->functionList)) {
            throw new MissingRequiredValueException('Function list cannot be empty.');
        }

        $request = ['Store_Code' => $this->storeCode];

        if ($this->addTimestamp) {
            $request['Miva_Request_Timestamp'] = time();
        }

        if (count($this->functionList) === 1) {
            $functionName = key($this->functionList);
            $functions = $this->functionList[$functionName];
            $functionCount = count($functions);

            $request['Function'] = $functionName;

            if ($functionCount === 1) {
                $request = array_merge($request, $functions[0]->getRequestParameters());
            } elseif ($functionCount > 1) {
                $request['Iterations'] = $functions;
            }
        } elseif (count($this->functionList) > 1) {
            $functionOperations = [];

            foreach ($this->functionList as $functionName => $functions) {
                $functionCount = count($functions);
                $operation = ['Function' => $functionName];

                if ($functionCount === 1) {
                    $operation = array_merge($operation, $functions[0]->getRequestParameters());
                } elseif ($functionCount > 1) {
                    $operation['Iterations'] = $functions;
                }

                $functionOperations[] = $operation;
            }

            $request['Operations'] = $functionOperations;
        }

        return $request;
    }

    /**
     * Set the function property with a new function builder instance and add function name to function list.
     */
    public function newFunction(string $name): self
    {
        $this->function = new FunctionBuilder($name);

        if (! isset($this->functionList[$name])) {
            $this->functionList[$name] = [];
        }

        return $this;
    }
}
