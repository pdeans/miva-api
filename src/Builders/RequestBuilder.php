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
     * FunctionBuilder instance
     *
     * @var \pdeans\Miva\Api\Builders\FunctionBuilder
     */
    public $function;

    /**
     * API request function list
     *
     * @var array
     */
    protected $function_list;

    /**
     * Miva store code
     *
     * @var string
     */
    public $store_code;

    /**
     * Flag for enabling/disabling Miva_Request_Timestamp parameter
     *
     * @var boolean
     */
    public $timestamp;

    /**
     * Construct RequestBuilder object
     *
     * @param string $store_code
     * @param bool   $timestamp
     */
    public function __construct(string $store_code, bool $timestamp = true)
    {
        if (!strlen($store_code)) {
            throw new MissingRequiredValueException('Store code must be provided.');
        }

        $this->function      = null;
        $this->function_list = [];
        $this->store_code    = $store_code;
        $this->timestamp     = $timestamp;
    }

    /**
     * Add current FunctionBuilder instance to function list
     *
     * @return self
     */
    public function addFunction()
    {
        $this->function_list[$this->function->name][] = $this->function;

        return $this;
    }

    /**
     * Get the API request function list
     *
     * @return array
     */
    public function getFunctionList()
    {
        return $this->function_list;
    }

    /**
     * Specify JSON serialization format
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        if (empty($this->function_list)) {
            throw new MissingRequiredValueException('Function list cannot be empty.');
        }

        $request = ['Store_Code' => $this->store_code];

        if ($this->timestamp) {
            $request['Miva_Request_Timestamp'] = time();
        }

        // Handy dandy function to auto-magically set parameter fields for
        // single function calls
        $setSingleFuncParams = function ($function_obj) {
            $params = [];

            foreach ($this->function->getCommonparameterList() as $parameter) {
                if (isset($function_obj->{$parameter})) {
                    $params[$this->function->formatParameterName($parameter)] = $function_obj->{$parameter};
                }
            }

            if (!empty($function_obj->parameter_list)) {
                foreach ($function_obj->parameter_list as $name => $value) {
                    $params[$this->function->formatParameterName($name)] = $value;
                }
            }

            if (!empty($function_obj->filter_list)) {
                $params['Filter'] = $function_obj->filter_list;
            }

            return $params;
        };

        if (count($this->function_list) === 1) {
            $function_name  = key($this->function_list);
            $functions      = $this->function_list[$function_name];
            $function_count = count($functions);

            $request['Function'] = $function_name;

            if ($function_count === 1) {
                $request = array_merge($request, $setSingleFuncParams($functions[0]));
            } elseif ($function_count > 1) {
                $request['Iterations'] = $functions;
            }
        } elseif (count($this->function_list) > 1) {
            $function_operations = [];

            foreach ($this->function_list as $function_name => $functions) {
                $function_count = count($functions);
                $operation     = ['Function' => $function_name];

                if ($function_count === 1) {
                    $operation = array_merge($operation, $setSingleFuncParams($functions[0]));
                } elseif ($function_count > 1) {
                    $operation['Iterations'] = $functions;
                }

                $function_operations[] = $operation;
            }

            $request['Operations'] = $function_operations;
        }

        return $request;
    }

    /**
     * Create a new FunctionBuilder instance
     *
     * @param string $name  The function name
     *
     * @return self
     */
    public function newFunction(string $name)
    {
        if (!isset($this->function_list[$name])) {
            $this->function_list[$name] = [];
        }

        $this->function = new FunctionBuilder($name);

        return $this;
    }
}
