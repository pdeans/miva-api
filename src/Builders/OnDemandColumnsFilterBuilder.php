<?php

namespace pdeans\Miva\Api\Builders;

/**
 * OnDemandColumnsFilterBuilder class
 *
 * Build an ondemandcolumns request function filter.
 */
class OnDemandColumnsFilterBuilder extends FilterBuilder
{
    /**
     * On-demand columns list
     *
     * @var array
     */
    public array $columns;

    /**
     * Create a new on-demand columns filter builder instance.
     */
    public function __construct(array $columns)
    {
        $this->columns = $columns;
    }

    /**
     * Define JSON serialization format.
     */
    public function jsonSerialize(): array
    {
        return $this->columns;
    }
}
