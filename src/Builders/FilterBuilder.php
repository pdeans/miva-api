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
     * API function name - for use with 'show' filters.
     *
     * @var string|null
     */
    protected string|null $functionName;

    /**
     * Filter name.
     *
     * @var string
     */
    public string $name;

    /**
     * Filter value.
     *
     * @var mixed
     */
    public mixed $value;

    /**
     * Filter value list.
     *
     * @var array
     */
    protected array $valueList;

    /**
     * Create a new filter builder instance.
     */
    public function __construct(string $name, mixed $value, string|null $functionName = null)
    {
        $this->name = trim($name);

        if ($this->name === '') {
            throw new InvalidValueException('Invalid value provided for "name".');
        }

        $this->value = $value;

        if ($this->isBlankValue($this->value)) {
            throw new InvalidValueException('Invalid value provided for "value".');
        }

        $this->valueList    = [];
        $this->functionName = $functionName;
    }

    /**
     * Add a filter to the filter value list.
     */
    public function addFilter(): self
    {
        $name = strtolower($this->name);

        if ($name === 'search') {
            if (isset($this->value[0])) {
                foreach ($this->value as $searchFilter) {
                    $this->validateSearchFilter($searchFilter);

                    $this->valueList[] = new SearchFilterBuilder(
                        $searchFilter['field'],
                        $searchFilter['operator'],
                        isset($searchFilter['value']) ? $searchFilter['value'] : null
                    );
                }
            } else {
                $this->validateSearchFilter($this->value);

                $this->valueList[] = new SearchFilterBuilder(
                    $this->value['field'],
                    $this->value['operator'],
                    isset($this->value['value']) ? $this->value['value'] : null
                );
            }
        } elseif ($name === 'ondemandcolumns') {
            $this->valueList = new OnDemandColumnsFilterBuilder($this->value);
        } elseif ($name === 'show') {
            $showFilter = new ShowFilterBuilder($this->functionName, $this->value);

            $this->name = $showFilter->getFilterName();
            $this->valueList = $showFilter;
        } else {
            $this->valueList = new GenericFilterBuilder($this->value);
        }

        return $this;
    }

    /**
     * Determine if a filter value is blank.
     */
    protected function isBlankValue(mixed $value): bool
    {
        if (is_null($value)) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_bool($value) || is_numeric($value)) {
            return false;
        }

        if ($value instanceof Countable) {
            return count($value) === 0;
        }

        return empty($value);
    }

    /**
     * Define JSON serialization format.
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'value' => $this->valueList,
        ];
    }

    /**
     * Validate a search filter.
     *
     * @throws \pdeans\Miva\Api\Exceptions\MissingRequiredValueException
     */
    protected function validateSearchFilter(array $filter): void
    {
        if (! isset($filter['field'])) {
            throw new MissingRequiredValueException('Missing required filter property "field".');
        }

        if (! isset($filter['operator'])) {
            throw new MissingRequiredValueException('Missing required filter property "operator".');
        }

        if (
            ! isset($filter['value'])
            && !in_array(strtoupper($filter['operator']), SearchFilterBuilder::getNullOperators())
        ) {
            throw new MissingRequiredValueException('Missing required filter property "value".');
        }
    }
}
