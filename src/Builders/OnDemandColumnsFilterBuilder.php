<?php

namespace pdeans\Miva\Api\Builders;

/**
 * OnDemandColumnsFilterBuilder class
 *
 * Build an ondemandcolumns request Function filter.
 */
class OnDemandColumnsFilterBuilder extends FilterBuilder
{
    /**
     * On-demand columns list
     *
     * @var array
     */
    public $columns;

    /**
     * Construct OnDemandColumnsFilterBuilder object
     *
     * @param array $columns  The list of on-demand columns
     */
    public function __construct(array $columns)
    {
        $this->columns = $columns;
    }

    /**
     * Specify JSON serialization format
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->columns;
    }
}
