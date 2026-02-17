<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Filter;

use ApiPlatform\Doctrine\Common\Filter\RangeFilterInterface;
use ApiPlatform\Doctrine\Odm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Odm\Filter\FilterInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ODM\MongoDB\Aggregation\Builder;

final class UlidRangeFilter extends AbstractFilter implements
    FilterInterface,
    RangeFilterInterface
{
    /**
     * @return array<string, array<string, string|bool>>
     */
    #[Override]
    public function getDescription(string $resourceClass): array
    {
        $operators = ['lt', 'lte', 'gt', 'gte', 'between'];
        $keys = array_keys($this->properties);

        return array_merge(
            ...array_map(
                static fn (
                    string $property
                ): array => self::buildDescriptionForProperty(
                    $property,
                    $operators
                ),
                $keys
            )
        );
    }

    /**
     * @param array<string, string> $context
     */
    #[Override]
    protected function filterProperty(
        string $property,
        mixed $value,
        Builder $aggregationBuilder,
        string $resourceClass,
        ?Operation $operation = null,
        array &$context = []
    ): void {
        $denormProp = $this->denormalizePropertyName($property);
        if (!$this->isFilterableProperty($denormProp, $resourceClass)) {
            return;
        }

        $this->applyUlidFilters(
            $denormProp,
            $this->normalizeValues($value),
            $aggregationBuilder
        );
    }

    /**
     * @return array<string, string|int|float|bool|array|null>
     */
    private function normalizeValues(mixed $value): array
    {
        return is_array($value) ? $value : [$value];
    }

    /**
     * @param array<string, string|int|float|bool|array|null> $values
     */
    private function applyUlidFilters(
        string $property,
        array $values,
        Builder $aggregationBuilder
    ): void {
        $processor = new UlidFilterProcessor();

        array_walk(
            $values,
            static function (
                string|int|float|bool|array|null $rawValue,
                string|int $operator
            ) use (
                $processor,
                $property,
                $aggregationBuilder
            ): void {
                $processor->process(
                    $property,
                    (string) $operator,
                    $rawValue,
                    $aggregationBuilder
                );
            }
        );
    }

    /**
     * @param array<string> $operators
     *
     * @return array<string, array<string, string|bool>>
     */
    private static function buildDescriptionForProperty(
        string $property,
        array $operators
    ): array {
        return array_combine(
            array_map(
                static fn (
                    string $op
                ): string => sprintf('%s[%s]', $property, $op),
                $operators
            ),
            array_map(
                static fn (
                    string $op
                ): array => self::buildOperatorDescription($property, $op),
                $operators
            )
        );
    }

    /**
     * @return array<string, string|bool>
     */
    private static function buildOperatorDescription(
        string $property,
        string $operator
    ): array {
        return [
            'property' => $property,
            'type' => 'string',
            'required' => false,
            'description' => sprintf(
                'Filter on the %s property using the %s operator',
                $property,
                $operator
            ),
        ];
    }

    private function isFilterableProperty(
        string $property,
        string $resourceClass
    ): bool {
        return $this->isPropertyEnabled($property, $resourceClass)
            && $this->isPropertyMapped($property, $resourceClass, true);
    }
}
