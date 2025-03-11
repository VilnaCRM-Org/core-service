<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Filter;

use ApiPlatform\Doctrine\Common\Filter\RangeFilterInterface;
use ApiPlatform\Doctrine\Odm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Odm\Filter\FilterInterface;
use ApiPlatform\Metadata\Operation;
use App\Shared\Domain\ValueObject\Ulid;
use Doctrine\ODM\MongoDB\Aggregation\Builder;

final class UlidRangeFilter extends AbstractFilter implements
    FilterInterface,
    RangeFilterInterface
{
    /**
     * @return array<string, array<string, string|bool>>
     */
    public function getDescription(string $resourceClass): array
    {
        if (!$this->properties) {
            return [];
        }
        $operators = ['lt', 'lte', 'gt', 'gte', 'between'];
        $keys = array_keys($this->properties);
        return array_merge(
            ...array_map(
                static fn (string $property): array =>
                self::buildDescriptionForProperty($property, $operators),
                $keys
            )
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
                static fn (string $op): array => [
                    'property' => $property,
                    'type' => 'string',
                    'required' => false,
                    'description' => sprintf(
                        'Filter on the %s property using the %s operator',
                        $property,
                        $op
                    ),
                ],
                $operators
            )
        );
    }

    /**
     * @param array<string, string> $context
     */
    protected function filterProperty(
        string $property,
        mixed $value,
        Builder $aggregationBuilder,
        string $resourceClass,
        ?Operation $operation = null,
        array &$context = []
    ): void {
        $denormProp = $this->denormalizePropertyName($property);
        if (
            !$this->isPropertyEnabled($denormProp, $resourceClass)
            || !$this->isPropertyMapped($denormProp, $resourceClass, true)
        ) {
            return;
        }

        $operators = is_array($value) ? $value : [$value];
        array_walk(
            $operators,
            function (
                $rawValue,
                $operator
            ) use (
                $denormProp,
                $aggregationBuilder
            ): void {
                if (
                    ($denormProp === 'ulid' ||
                        str_ends_with($denormProp, 'ulid')) &&
                    is_string($rawValue)
                ) {
                    $parsedValue = $this->parseUlidValue($rawValue);
                    if ($parsedValue !== null) {
                        $this->applyOperator(
                            $operator,
                            $parsedValue,
                            $denormProp,
                            $aggregationBuilder
                        );
                    }
                }
            }
        );
    }

    private function parseUlidValue(string $value): Ulid|array|null
    {
        if (str_contains($value, '..')) {
            $parts = explode('..', $value, 2);
            if (count($parts) !== 2) {
                return null;
            }
            try {
                $min = new Ulid(trim($parts[0]));
                $max = new Ulid(trim($parts[1]));
            } catch (\InvalidArgumentException $e) {
                return null;
            }
            return [$min, $max];
        }
        try {
            return new Ulid($value);
        } catch (\InvalidArgumentException $e) {
            return null;
        }
    }

    private function applyOperator(
        string $operator,
        Ulid|array $filterValue,
        string $field,
        Builder $builder
    ): void {
        $class = __NAMESPACE__ . '\\' . ucfirst($operator);
        /** @var OperatorStrategyInterface $operatorStrategy */
        $operatorStrategy = new $class();

        $operatorStrategy->apply($builder, $field, $filterValue);
    }
}
