<?php

namespace pdeans\Miva\Api\Builders;

/**
 * GenericFilterBuilder class
 *
 * Build a generic function filter. A generic filter will be created
 * for any filter passed into the function builder that does not correspond
 * to an individual filter builder class.
 */
class GenericFilterBuilder extends FilterBuilder
{
    /**
     * Filter value.
     *
     * @var mixed
     */
    public mixed $value;

    /**
     * Create a new generic filter builder instance.
     */
    public function __construct(mixed $value)
    {
        $this->value = $value;
    }

    /**
     * Define JSON serialization format.
     */
    public function jsonSerialize(): mixed
    {
        return $this->value;
    }
}
