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
     * Filter search field.
     *
     * @var string
     */
    public string $field;

    /**
     * List of valid NULL search operators.
     *
     * @var array
     */
    protected static array $NULL_OPERATORS = [
        'TRUE',  // "field" is true
        'FALSE', // "field" is false
        'NULL',  // "field" is null
    ];

    /**
     * Filter search operator.
     *
     * @var string
     */
    public string $operator;

    /**
     * List of valid search operators.
     *
     * @var array
     */
    protected static array $OPERATORS = [
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
     * Filter search value.
     *
     * @var string|null
     */
    public string|null $value;

    /**
     * Create a new search filter builder instance.
     *
     * @throws \pdeans\Miva\Api\Exceptions\InvalidValueException
     */
    public function __construct(string $field, string $operator, mixed $value = null)
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
     * Get the search null operators list.
     */
    public static function getNullOperators(): array
    {
        return self::$NULL_OPERATORS;
    }

    /**
     * Get the search filter operator and value.
     *
     * @throws \pdeans\Miva\Api\Exceptions\InvalidArgumentException
     */
    public static function getOperatorAndValue(string $operator, mixed $value = null): array
    {
        $isNullOperator = in_array(strtoupper($operator), self::getNullOperators());

        if (is_null($value) && !$isNullOperator) {
            return ['EQ', $operator];
        }

        if ($isNullOperator) {
            return [$operator, null];
        }

        if (self::isInvalidOperatorAndValue($operator, $value)) {
            throw new InvalidArgumentException('Invalid operator and value search filter combination.');
        }

        return self::isInvalidOperator($operator) ? ['EQ', $operator] : [$operator, $value];
    }

    /**
     * Get the search operators list.
     */
    public static function getOperators(): array
    {
        return self::$OPERATORS;
    }

    /**
     * Determine if a search operator is valid.
     */
    public static function isInvalidOperator(mixed $operator): bool
    {
        return ! is_string($operator) || ! in_array(strtoupper($operator), self::getOperators());
    }

    /**
     * Determine if search operator and value combination are valid.
     */
    public static function isInvalidOperatorAndValue(mixed $operator, mixed $value): bool
    {
        $operator = strtoupper($operator);

        return is_null($value)
            && in_array($operator, self::getOperators())
            && ! in_array($operator, self::getNullOperators());
    }

    /**
     * Define JSON serialization format.
     */
    public function jsonSerialize(): array
    {
        $params = [
            'field' => $this->field,
            'operator' => $this->operator,
        ];

        if (! is_null($this->value)) {
            $params['value'] = $this->value;
        }

        return $params;
    }
}
