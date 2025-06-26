<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Filter;

use App\Shared\Domain\ValueObject\Ulid;
use Doctrine\ODM\MongoDB\Aggregation\Builder;

/**
 * @psalm-suppress UnusedClass
 */
final class Gt implements OperatorStrategyInterface
{
    public function apply(Builder $builder, string $field, mixed $value): void
    {
        $fieldName = sprintf('$%s', $field);
        $binaryValue = $value instanceof Ulid ? $value->toBinary() : $value;

        $builder->match([
            $fieldName => ['$gt' => $binaryValue]
        ]);
    }
}
