<?php

/*
 *
 * Miva Merchant
 *
 * This file and the source codes contained herein are the property of
 * Miva, Inc. Use of this file is restricted to the specific terms and
 * conditions in the License Agreement associated with this file. Distribution
 * of this file or portions of this file for uses not covered by the License
 * Agreement is not allowed without a written agreement signed by an officer of
 * Miva, Inc.
 *
 * Copyright 1998-2025 Miva, Inc. All rights reserved.
 * https://www.miva.com
 *
 */

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
