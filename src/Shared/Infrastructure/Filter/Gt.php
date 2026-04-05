<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Filter;

use Doctrine\ODM\MongoDB\Aggregation\Builder;

/**
 * @psalm-suppress UnusedClass
 */
final class Gt implements OperatorStrategyInterface
{
    public function apply(
        Builder $builder,
        string $field,
        mixed $filterValue
    ): void {
        $builder->match()->field($field)->gt($filterValue);
    }
}
