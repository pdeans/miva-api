<?php

namespace pdeans\Miva\Api\Builders;

/**
 * GenericFilterBuilder class
 *
 * Build a "generic" Function filter. A "generic" filter will be created
 * for any filter passed into the FunctionBuilder that does not correspond
 * to a filter Builder class.
 */
class GenericFilterBuilder extends FilterBuilder
{
    /**
     * Filter value
     *
     * @var mixed
     */
    public $value;

    /**
     * Construct GenericFilterBuilder object
     *
     * @param mixed $value  The filter value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Specify JSON serialization format
     *
     * @return string
     */
    public function jsonSerialize()
    {
        return $this->value;
    }
}
