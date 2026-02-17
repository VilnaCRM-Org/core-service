<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Filter;

use Doctrine\ODM\MongoDB\Aggregation\Builder;

final class Lte implements OperatorStrategyInterface
{
    #[Override]
    public function apply(
        Builder $builder,
        string $field,
        mixed $filterValue
    ): void {
        $builder->match()->field($field)->lte($filterValue);
    }
}
