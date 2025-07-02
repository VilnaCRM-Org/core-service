<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Filter;

use Doctrine\ODM\MongoDB\Aggregation\Builder;

final class Lte implements OperatorStrategyInterface
{
    public function apply(
        Builder $builder,
        string $field,
        \App\Shared\Domain\ValueObject\Ulid $filterValue
    ): void {
        $builder->match()->field($field)->lte($filterValue);
    }
}
