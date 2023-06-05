<?php

namespace pdeans\Miva\Api\Builders;

use pdeans\Miva\Api\Exceptions\InvalidArgumentException;
use pdeans\Miva\Api\Exceptions\InvalidValueException;

/**
 * SearchFilterBuilder class
 */
class SearchFilterBuilder extends FilterBuilder
{
    /**
     * Filter search field
     *
     * @var string
     */
    public $field;

    /**
     * List of valid NULL search operators
     *
     * @var array
     */
    protected static $NULL_OPERATORS = [
        'TRUE',  // "field" is true
        'FALSE', // "field" is false
        'NULL',  // "field" is null
    ];

    /**
     * Filter serach operator
     *
     * @var string
     */
    public $operator;

    /**
     * List of valid search operators
     *
     * @var array
     */
    protected static $OPERATORS = [
        'EQ',       // "field" equals "value" (generally case insensitive)
        'GT',       // "field" is greater than to "value"
        'GE',       // "field" is greater than or equal to "value"
        'LT',       // "field" is less than "value"
        'LE',       // "field" is less than or equal to "value"
        'CO',       // "field" contains "value"
        'NC',       // "field" does not contain "value"
        'LIKE',     // "field" matches "value" using SQL LIKE semantics
        'NOTLIKE',  // "field" does not match "value" using SQL LIKE semantics
        'NE',       // "field" is not equal to "value"
        'TRUE',     // "field" is true
        'FALSE',    // "field" is false
        'NULL',     // "field" is null
        'IN',       // "field" is equal to one of a set of comma-separated values taken from "value"
        'SUBWHERE', // Used for parenthetical comparisons
    ];

    /**
     * Filter search value
     *
     * @var string|null
     */
    public $value;

    /**
     * Construct SearchFilterBuilder object
     *
     * @param string $field    The search field name
     * @param string $operator The search operator
     * @param mixed  $value    The search value
     */
    public function __construct(string $field, string $operator, $value = null)
    {
        $this->field = trim($field);

        if ($this->field === '') {
            throw new InvalidValueException('Invalid value provided for "field".');
        }

        $this->operator = strtoupper($operator);

        if (!in_array($this->operator, self::$OPERATORS)) {
            throw new InvalidValueException('Invalid operator "' . $operator . '" provided.');
        }

        if (is_null($value) && !in_array($this->operator, self::$NULL_OPERATORS)) {
            throw new InvalidValueException('Invalid value provided for "value".');
        }

        $this->value = $value;
    }

    /**
     * Get the search null operators list
     *
     * @return array
     */
    public static function getNullOperators()
    {
        return self::$NULL_OPERATORS;
    }

    /**
     * Get the search filter operator and value
     *
     * @param string $operator
     * @param mixed  $value
     *
     * @return array
     */
    public static function getOperatorAndValue(string $operator, $value = null)
    {
        $is_null_operator = (in_array(strtoupper($operator), self::getNullOperators()));

        if (is_null($value) && !$is_null_operator) {
            return ['EQ', $operator];
        } elseif ($is_null_operator) {
            return [$operator, null];
        } elseif (self::isInvalidOperatorAndValue($operator, $value)) {
            throw new InvalidArgumentException('Invalid operator and value search filter combination.');
        }

        return (self::isInvalidOperator($operator) ? ['EQ', $operator] : [$operator, $value]);
    }

    /**
     * Get the search operators list
     *
     * @return array
     */
    public static function getOperators()
    {
        return self::$OPERATORS;
    }

    /**
     * Determine if search operator is valid
     *
     * @param mixed $operator
     *
     * @return boolean
     */
    public static function isInvalidOperator($operator)
    {
        return (!is_string($operator) || !in_array(strtoupper($operator), self::getOperators()));
    }

    /**
     * Determine if search operator and value combination are valid
     *
     * @param mixed $operator
     * @param mixed $value
     *
     * @return boolean
     */
    public static function isInvalidOperatorAndValue($operator, $value)
    {
        $operator = strtoupper($operator);

        return (
            is_null($value)
            && in_array(strtoupper($operator), self::getOperators())
            && ! in_array($operator, self::getNullOperators())
        );
    }

    /**
     * Specify JSON serialization format
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        $params = [
            'field'    => $this->field,
            'operator' => $this->operator,
        ];

        if (! is_null($this->value)) {
            $params['value'] = $this->value;
        }

        return $params;
    }
}
