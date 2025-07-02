<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Filter;

use Doctrine\ODM\MongoDB\Aggregation\Builder;

final class Between implements OperatorStrategyInterface
{
    /**
     * @param \App\Shared\Domain\ValueObject\Ulid|\App\Shared\Domain\ValueObject\Ulid[] $filterValue
     *
     * @psalm-param \App\Shared\Domain\ValueObject\Ulid|list{\App\Shared\Domain\ValueObject\Ulid, \App\Shared\Domain\ValueObject\Ulid} $filterValue
     */
    public function apply(
        Builder $builder,
        string $field,
        mixed $filterValue
    ): void {
        if (count($filterValue) === 2) {
            $builder->match()
                ->field($field)
                ->gte($filterValue[0])
                ->lte($filterValue[1]);
        }
    }
}
