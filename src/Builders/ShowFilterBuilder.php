<?php

namespace pdeans\Miva\Api\Builders;

use pdeans\Miva\Api\Exceptions\InvalidValueException;

/**
 * ShowFilterBuilder class
 */
class ShowFilterBuilder extends FilterBuilder
{
    /**
     * API function name
     *
     * @var string
     */
    protected $function_name;

    /**
     * List of valid API function names
     *
     * @var array
     */
    protected static $FUNCTION_NAMES = [
        'categorylist_load_query',
        'categoryproductlist_load_query',
        'productlist_load_query',
    ];

    /**
     * Show filter value
     *
     * @var string
     */
    public $show_value;

    /**
     * Construct ProductShowFilterBuilder object
     *
     * @param string $function_name The API function name
     * @param string $show_value    The show filter value
     */
    public function __construct(string $function_name, string $show_value)
    {
        $this->function_name = strtolower($function_name);

        if (!$this->isValidFunctionName($this->function_name)) {
            throw new InvalidValueException('Show filter is not supported for function "'.$function_name.'".');
        }

        $this->show_value = ucfirst($show_value);

        if (!$this->isValidShowValue($this->show_value)) {
            throw new InvalidValueException('Invalid value "'.$show.'" provided to show filter.');
        }
    }

    /**
     * Get the show filter name
     *
     * @return string
     */
    public function getFilterName()
    {
        switch (strtolower($this->function_name)) {
            case 'categorylist_load_query':
                return 'Category_Show';
            case 'categoryproductlist_load_query':
            case 'productlist_load_query':
                return 'Product_Show';
        }

        throw new InvalidValueException('Show filter not found for function "'.$this->function_name.'".');
    }

    /**
     * Determine if function name is valid for show filter
     *
     * @param string $function_name
     *
     * @return boolean
     */
    protected function isValidFunctionName(string $function_name)
    {
        return (in_array(strtolower($function_name), self::$FUNCTION_NAMES));
    }

    /**
     * Determine if show filter value is valid
     *
     * @param string $show_value
     *
     * @return boolean
     */
    protected function isValidShowValue(string $show_value)
    {
        $show_values = [];

        switch (strtolower($this->function_name)) {
            case 'categorylist_load_query':
                $show_values = ['Active', 'All'];
                break;
            case 'categoryproductlist_load_query':
            case 'productlist_load_query':
                $show_values = ['Active', 'All', 'Uncategorized'];
                break;
        }

        if (empty($show_values)) {
            return false;
        }

        return in_array(ucfirst($show_value), $show_values);
    }

    /**
     * Specify JSON serialization format
     *
     * @return string
     */
    public function jsonSerialize(): string
    {
        return $this->show_value;
    }
}
