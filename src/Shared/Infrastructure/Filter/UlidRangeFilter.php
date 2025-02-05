<?php

namespace App\Shared\Infrastructure\Filter;

use ApiPlatform\Doctrine\Common\Filter\RangeFilterInterface;
use ApiPlatform\Doctrine\Odm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Odm\Filter\FilterInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Symfony\Component\Uid\Ulid;

final class UlidRangeFilter extends AbstractFilter implements FilterInterface, RangeFilterInterface
{
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
            !$this->isPropertyMapped($denormalizedProperty, $resourceClass, true)
        ) {
            return;
        }

        $operators = \is_array($value) ? $value : [$value];

        foreach ($operators as $operator => $filterValue) {
            if (
                (('ulid' === $denormalizedProperty) || (str_ends_with($denormalizedProperty, 'ulid')))
                && \is_string($filterValue)
            ) {
                try {
                    $filterValue = new Ulid($filterValue);
                } catch (\InvalidArgumentException $e) {
                    continue;
                }
            }

            $matchStage = $aggregationBuilder->match();
            switch ($operator) {
                case 'lt':
                    $matchStage->field($denormalizedProperty)->lt($filterValue);
                    break;
                case 'lte':
                    $matchStage->field($denormalizedProperty)->lte($filterValue);
                    break;
                case 'gt':
                    $matchStage->field($denormalizedProperty)->gt($filterValue);
                    break;
                case 'gte':
                    $matchStage->field($denormalizedProperty)->gte($filterValue);
                    break;
                case 'between':
                    // For "between", we expect an array with exactly two elements.
                    if (\is_array($filterValue) && \count($filterValue) === 2) {
                        [$min, $max] = $filterValue;
                        if (('ulid' === $denormalizedProperty) || (str_ends_with($denormalizedProperty, 'ulid'))) {
                            try {
                                $minUlid = new Ulid($min);
                                $maxUlid = new Ulid($max);
                                $min = $minUlid->toString();
                                $max = $maxUlid->toString();
                            } catch (\InvalidArgumentException $e) {
                                continue 2;
                            }
                        }
                        $matchStage->field($denormalizedProperty)->gte($min)->lte($max);
                    }
                    break;
                default:
                    continue 2;
            }
        }
    }

    public function getDescription(string $resourceClass): array
    {
        if (null === $this->properties) {
            return [];
        }
        $description = [];
        foreach ($this->properties as $property => $strategy) {
            foreach (['lt', 'lte', 'gt', 'gte', 'between'] as $operator) {
                $description[sprintf('%s[%s]', $property, $operator)] = [
                    'property'    => $property,
                    'type'        => 'string',
                    'required'    => false,
                    'description' => sprintf('Filter on the %s property using the %s operator', $property, $operator),
                ];
            }
        }
        return $description;
    }
}
