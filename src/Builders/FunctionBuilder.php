<?php

namespace pdeans\Miva\Api\Builders;

use pdeans\Miva\Api\Contracts\BuilderInterface;
use pdeans\Miva\Api\Exceptions\InvalidValueException;
use pdeans\Miva\Api\Exceptions\MissingRequiredValueException;

/**
 * FunctionBuilder class
 *
 * Build a request Function.
 */
class FunctionBuilder implements BuilderInterface
{
    /**
     * Number of records to return
     *
     * @var null|int
     */
    public $count;

    /**
     * List of FilterBuilder objects
     *
     * @var array
     */
    public $filter_list;

    /**
     * Function name
     *
     * @var string
     */
    public $name;

    /**
     * Number of records to offset the return count
     *
     * @var null|int
     */
    public $offset;

    /**
     * Function parameter list
     *
     * @var array
     */
    public $parameter_list;

    /**
     * Encryption passphrase
     *
     * @var null|string
     */
    public $passphrase;

    /**
     * Sort records modifier
     *
     * @var null|string
     */
    public $sort;

    /**
     * Construct FunctionBuilder object
     *
     * @param string $name  The function name
     */
    public function __construct(string $name)
    {
        if (trim($name) === '') {
            throw new MissingRequiredValueException('Invalid function name "'.$name.'" provided.');
        }

        $this->name           = $name;
        $this->count          = null;
        $this->filter_list    = [];
        $this->offset         = null;
        $this->parameter_list = [];
        $this->passphrase     = null;
        $this->sort           = null;
    }

    /**
     * Set the number of records to return
     *
     * @param int $count
     *
     * @return self
     */
    public function count(int $count)
    {
        $this->count = $count;

        return $this;
    }

    /**
     * Create a filter and add it to the filter list
     *
     * @param string $filter_name  The filter name
     * @param mixed  $filter_value The filter value
     *
     * @return self
     */
    public function filter(string $filter_name, $filter_value)
    {
        if (trim($filter_name) === '') {
            throw new InvalidValueException('Invalid value "'.$filter_name.'" provided for filter name.');
        }

        $this->filter_list[] = (new FilterBuilder($filter_name, $filter_value))->addFilter();

        return $this;
    }

    /**
     * Create a collection of filters and add them to the filter list
     *
     * @param array $filters  The collection of filters to create (filter name => filter value)
     *
     * @return self
     */
    public function filters(array $filters)
    {
        foreach ($filters as $filter_name => $filter_value) {
            $this->filter($filter_name, $filter_value);
        }

        return $this;
    }

    /**
     * Format the parmater name
     *
     * @param string $param_name  The parameter name
     *
     * @return self
     */
    public function formatParameterName(string $param_name)
    {
        return mb_convert_case($param_name, MB_CASE_TITLE);
    }

    /**
     * Get the list of common parameters.
     *
     * This list maps corresponding helper methods within the class, with the
     * method name matching the parameter list value.
     *
     * @return array
     */
    public function getCommonParameterList()
    {
        return [
            'count',
            'offset',
            'passphrase',
            'sort',
        ];
    }

    /**
     * Get the full parameter list
     *
     * @return array
     */
    public function getParameterList()
    {
        return array_merge($this->getCommonParameterList(), $this->parameter_list);
    }

    /**
     * Specify JSON serialization format
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        $function = [];

        foreach ($this->getCommonParameterList() as $parameter) {
            if (isset($this->{$parameter})) {
                $function[$this->formatParameterName($parameter)] = $this->{$parameter};
            }
        }

        if (!empty($this->parameter_list)) {
            foreach ($this->parameter_list as $name => $value) {
               $function[$this->formatParameterName($name)] = $value;
            }
        }

        if (!empty($this->filter_list)) {
            $function['Filter'] = $this->filter_list;
        }

        return $function;
    }

    /**
     * Set the offset of the first record to return
     *
     * @param int $offset  0-based offset of the first record to return
     *
     * @return self
     */
    public function offset(int $offset)
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Shorthand method to set the ondemandcolumns filter list
     *
     * @param array $columns  The on demand columns
     *
     * @return self
     */
    public function odc(array $columns)
    {
        $this->ondemandcolumns($columns);

        return $this;
    }

    /**
     * Set the ondemandcolumns filter list
     *
     * @param array $columns  The on demand columns
     *
     * @return self
     */
    public function ondemandcolumns(array $columns)
    {
        $this->filter('ondemandcolumns', $columns);

        return $this;
    }

    /**
     * Set additional Function input parameters
     *
     * @param array $parameters  The list of input parameters (parameter name => parameter value)
     *
     * @return self
     */
    public function params(array $parameters)
    {
        $this->parameter_list = $parameters;

        return $this;
    }

    /**
     * Set the Passphrase parameter
     *
     * @param string $passphrase  The payment decryption passphrase
     *
     * @return self
     */
    public function passphrase(string $passphrase)
    {
        $this->passphrase = $passphrase;

        return $this;
    }

    /**
     * Create a search filter
     *
     * @param array $args
     *
     * @return self
     */
    public function search(...$args)
    {
        $args_count = count($args);

        if ($args_count < 1 || $args_count > 3) {
            throw new InvalidValueException('Invalid arguments supplied for "'.__METHOD__.'".');
        }

        if ($args_count === 1) {
            if (!is_array($args[0]) || empty($args[0])) {
                throw new InvalidValueException('Invalid arguments supplied for "'.__METHOD__.'".');
            }

            $this->filter('search', $args[0]);
        } else {
            $field = array_shift($args);

            [$operator, $value] = SearchFilterBuilder::getOperatorAndValue(...$args);

            $this->filter('search', [
                'field'    => $field,
                'operator' => strtoupper($operator),
                'value'    => $value,
            ]);
        }

        return $this;
    }

    /**
     * Create a show filter
     *
     * @param string $show_value  The show value
     *
     * @return self
     */
    public function show(string $show_value)
    {
        $this->filter_list[] = (new FilterBuilder('show', $show_value, $this->name))->addFilter();

        return $this;
    }

    /**
     * Set the column to sort results
     *
     * @param string $sort_column  The column name to sort the results
     *
     * @return self
     */
    public function sort(string $sort_column)
    {
        $this->sort = strtolower($sort_column);

        return $this;
    }

    /**
     * Set the column to sort results in descending order
     *
     * @param string $sort_column  The column name to sort the results
     *
     * @return self
     */
    public function sortDesc(string $sort_column)
    {
        $this->sort = '-'.strtolower(str_replace('-', '', $sort_column));

        return $this;
    }
}
