<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Filter;

use Doctrine\ODM\MongoDB\Aggregation\Builder;

final class Between implements OperatorStrategyInterface
{
    #[\Override]
    public function apply(
        Builder $builder,
        string $field,
        mixed $filterValue
    ): void {
        if (is_array($filterValue)) {
            [$min, $max] = $filterValue;
            $builder->match()->field($field)
                ->gte($min)
                ->lte($max);
        }
    }
}
