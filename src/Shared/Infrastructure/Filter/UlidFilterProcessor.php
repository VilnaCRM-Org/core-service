<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Filter;

use App\Shared\Domain\ValueObject\Ulid;
use Doctrine\ODM\MongoDB\Aggregation\Builder;

final class UlidFilterProcessor
{
    /**
     * @psalm-param 123|string $rawValue
     */
    public function process(
        string $property,
        string $operator,
        int|string $rawValue,
        Builder $builder
    ): void {
        if (!$this->isUlidProperty($property) || !is_string($rawValue)) {
            return;
        }

        $parsedValue = $this->parseUlidValue($rawValue);

        $this->applyOperator($operator, $parsedValue, $property, $builder);
    }

    private function isUlidProperty(string $property): bool
    {
        return str_ends_with($property, 'ulid');
    }

    private function parseUlidValue(string $value): Ulid|array|null
    {
        if (str_contains($value, '..')) {
            $parts = explode('..', $value, 2);
            $min = new Ulid(trim($parts[0]));
            $max = new Ulid(trim($parts[1]));
            return [$min, $max];
        }
        return new Ulid($value);
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
