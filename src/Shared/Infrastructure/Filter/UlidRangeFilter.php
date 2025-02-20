<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Filter;

use ApiPlatform\Doctrine\Common\Filter\RangeFilterInterface;
use ApiPlatform\Doctrine\Odm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Odm\Filter\FilterInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Symfony\Component\Uid\Ulid;

final class UlidRangeFilter extends AbstractFilter implements FilterInterface, RangeFilterInterface
{
    /**
     * Filters a property based on the provided operator and ULID value(s).
     *
     * @param string|object $value
     * @param array<string, string> $context
     */
    protected function filterProperty(
        string $property,
               $value,
        Builder $aggregationBuilder,
        string $resourceClass,
        ?Operation $operation = null,
        array &$context = []
    ): void {
        $denormalizedProperty = $this->denormalizePropertyName($property);
        if (
            !$this->isPropertyEnabled($denormalizedProperty, $resourceClass) ||
            !$this->isPropertyMapped(
                $denormalizedProperty,
                $resourceClass,
                true
            )
        ) {
            return;
        }
        $operators = \is_array($value) ? $value : [$value];

        foreach ($operators as $operator => $filterValue) {
            if (
                ($denormalizedProperty === 'ulid' ||
                    str_ends_with($denormalizedProperty, 'ulid')) &&
                \is_string($filterValue)
            ) {
                if (str_contains($filterValue, '..')) {
                    $parts = explode('..', $filterValue, 2);
                    if (count($parts) === 2) {
                        try {
                            $min = new Ulid(trim($parts[0]));
                            $max = new Ulid(trim($parts[1]));
                            $filterValue = [$min, $max];
                        } catch (\InvalidArgumentException $e) {
                            continue;
                        }
                    }
                } else {
                    try {
                        $filterValue = new Ulid($filterValue);
                    } catch (\InvalidArgumentException) {
                        continue;
                    }
                }
                $matchStage = $aggregationBuilder->match();
                switch ($operator) {
                    case 'lt':
                        $matchStage->field($denormalizedProperty)
                            ->lt($filterValue);
                        break;
                    case 'lte':
                        $matchStage->field($denormalizedProperty)
                            ->lte($filterValue);
                        break;
                    case 'gt':
                        $matchStage->field($denormalizedProperty)
                            ->gt($filterValue);
                        break;
                    case 'gte':
                        $matchStage->field($denormalizedProperty)
                            ->gte($filterValue);
                        break;
                    case 'between':
                        if (\is_array($filterValue)) {
                            [$min, $max] = $filterValue;
                            $matchStage->field($denormalizedProperty)
                                ->gte($min)->lte($max);
                        }
                        break;
                    default:
                        continue 2;
                }
            }
        }
    }

    /**
     * @return array<string, array<string, string|bool>>
     */
    public function getDescription(string $resourceClass): array
    {
        if ($this->properties === null) {
            return [];
        }
        $description = [];
        foreach ($this->properties as $property => $strategy) {
            foreach (['lt', 'lte', 'gt', 'gte', 'between'] as $operator) {
                $description[sprintf('%s[%s]', $property, $operator)] = [
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
        }
        return $description;
    }
}
