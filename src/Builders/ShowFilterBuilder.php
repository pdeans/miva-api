<?php

namespace pdeans\Miva\Api\Builders;

use pdeans\Miva\Api\Exceptions\InvalidValueException;

/**
 * ShowFilterBuilder class
 */
class ShowFilterBuilder extends FilterBuilder
{
    /**
     * API function name.
     *
     * @var string|null
     */
    protected string|null $functionName;

    /**
     * List of valid API function names.
     *
     * @var array
     */
    protected static array $FUNCTION_NAMES = [
        'categorylist_load_query',
        'categoryproductlist_load_query',
        'productlist_load_query',
    ];

    /**
     * Show filter value.
     *
     * @var string
     */
    public string $showValue;

    /**
     * Create a new show filter builder instance.
     *
     * @throws \pdeans\Miva\Api\Exceptions\InvalidValueException
     */
    public function __construct(string $functionName, string $showValue)
    {
        $this->functionName = strtolower($functionName);

        if (!$this->isValidFunctionName($this->functionName)) {
            throw new InvalidValueException('Show filter is not supported for function "' . $functionName . '".');
        }

        $this->showValue = ucfirst($showValue);

        if (!$this->isValidShowValue($this->showValue)) {
            throw new InvalidValueException('Invalid value "' . $showValue . '" provided to show filter.');
        }
    }

    /**
     * Get the show filter name.
     *
     * @throws \pdeans\Miva\Api\Exceptions\InvalidValueException
     */
    public function getFilterName(): string
    {
        switch (strtolower($this->functionName)) {
            case 'categorylist_load_query':
                return 'Category_Show';
            case 'categoryproductlist_load_query':
            case 'productlist_load_query':
                return 'Product_Show';
        }

        throw new InvalidValueException('Show filter not found for function "' . $this->functionName . '".');
    }

    /**
     * Determine if a function name is valid for the show filter.
     */
    protected function isValidFunctionName(string $functionName): bool
    {
        return (in_array(strtolower($functionName), self::$FUNCTION_NAMES));
    }

    /**
     * Determine if a show filter value is valid.
     */
    protected function isValidShowValue(string $showValue): bool
    {
        $showValues = [];

        switch (strtolower($this->functionName)) {
            case 'categorylist_load_query':
                $showValues = ['active', 'all'];
                break;
            case 'categoryproductlist_load_query':
            case 'productlist_load_query':
                $showValues = ['active', 'all', 'uncategorized'];
                break;
        }

        if (empty($showValues)) {
            return false;
        }

        return in_array(strtolower($showValue), $showValues);
    }

    /**
     * Define JSON serialization format.
     */
    public function jsonSerialize(): string
    {
        return $this->showValue;
    }
}
