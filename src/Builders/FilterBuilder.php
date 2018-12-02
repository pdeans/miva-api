<?php

namespace pdeans\Miva\Api\Builders;

use Countable;
use pdeans\Miva\Api\Contracts\BuilderInterface;
use pdeans\Miva\Api\Exceptions\InvalidValueException;
use pdeans\Miva\Api\Exceptions\MissingRequiredValueException;

/**
 * FilterBuilder class
 *
 * Build a request Function filter.
 */
class FilterBuilder implements BuilderInterface
{
    /**
     * API function name - for use with 'show' filters
     *
     * @var null|string
     */
    protected $function_name;

    /**
     * Filter name
     *
     * @var string
     */
    public $name;

    /**
     * Filter value
     *
     * @var mixed
     */
    public $value;

    /**
     * Filter value list
     *
     * @var array
     */
    protected $value_list;

    /**
     * Construct FilterBuilder object
     *
     * @param string $name  The filter name
     * @param mixed  $value The filter value
     */
    public function __construct(string $name, $value, $function_name = null)
    {
        $this->name = trim($name);

        if ($this->name === '') {
            throw new InvalidValueException('Invalid value provided for "name".');
        }

        $this->value = $value;

        if ($this->isBlankValue($this->value)) {
            throw new InvalidValueException('Invalid value provided for "value".');
        }

        $this->value_list    = [];
        $this->function_name = $function_name;
    }

    /**
     * Add filter to filter value list
     *
     * @return self
     */
    public function addFilter()
    {
        $name = strtolower($this->name);

        if ($name === 'search') {
            $validateSearch = function($search) {
                if (!isset($search['field'])) {
                    throw new MissingRequiredValueException('Missing required filter property "field".');
                } elseif (!isset($search['operator'])) {
                    throw new MissingRequiredValueException('Missing required filter property "operator".');
                } elseif (
                    !isset($search['value']) &&
                    !in_array(strtoupper($search['operator']), SearchFilterBuilder::getNullOperators())
                ) {
                    throw new MissingRequiredValueException('Missing required filter property "value".');
                }
            };

            if (isset($this->value[0])) {
                foreach ($this->value as $search_filter) {
                    $validateSearch($search_filter);

                    $this->value_list[] = new SearchFilterBuilder(
                        $search_filter['field'],
                        $search_filter['operator'],
                        (isset($search_filter['value']) ? $search_filter['value'] : null)
                    );
                }
            } else {
                $validateSearch($this->value);

                $this->value_list[] = new SearchFilterBuilder(
                    $this->value['field'],
                    $this->value['operator'],
                    (isset($this->value['value']) ? $this->value['value'] : null)
                );
            }
        } elseif ($name === 'ondemandcolumns') {
            $this->value_list = new OnDemandColumnsFilterBuilder($this->value);
        } elseif ($name === 'show') {
            $show_filter      = new ShowFilterBuilder($this->function_name, $this->value);
            $this->name       = $show_filter->getFilterName();
            $this->value_list = $show_filter;
        } else {
            $this->value_list = new GenericFilterBuilder($this->value);
        }

        return $this;
    }

    /**
     * Determine if value is blank
     *
     * @param mixed $val
     *
     * @return boolean
     */
    protected function isBlankValue($val)
    {
        if ($val === null) {
            return true;
        } elseif (is_bool($val) || is_numeric($val)) {
            return false;
        } elseif (is_string($val)) {
            return (trim($val) === '');
        } elseif ($val instanceof Countable) {
            return (count($val) === 0);
        }

        return empty($val);
    }

    /**
     * Specify JSON serialization format
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'name'  => $this->name,
            'value' => $this->value_list,
        ];
    }
}
