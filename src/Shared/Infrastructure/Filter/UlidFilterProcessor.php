<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Filter;

use App\Shared\Domain\ValueObject\Ulid;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Symfony\Component\Uid\Ulid as SymfonyUlid;

final class UlidFilterProcessor
{
    public function process(
        string $property,
        string $operator,
        string|array|int|float|bool|null $rawValue,
        Builder $builder
    ): void {
        if (! $this->canProcess($property, $rawValue)) {
            return;
        }

        $parsedValue = $this->parseUlidValue($rawValue);
        if ($parsedValue === null) {
            return;
        }

        $this->applyOperator($operator, $parsedValue, $property, $builder);
    }

    private function canProcess(string $property, string|array|int|float|bool|null $rawValue): bool
    {
        return $this->isUlidProperty($property)
            && is_string($rawValue);
    }

    private function isUlidProperty(string $property): bool
    {
        return str_ends_with($property, 'ulid');
    }

    private function parseUlidValue(string $value): Ulid|array|null
    {
        if (str_contains($value, '..')) {
            $parts = array_map(trim(...), explode('..', $value, 2));
            if (
                count($parts) !== 2
                || ! $this->isValidUlid($parts[0])
                || ! $this->isValidUlid($parts[1])
            ) {
                return null;
            }

            $min = new Ulid($parts[0]);
            $max = new Ulid($parts[1]);
            return [$min, $max];
        }

        $value = trim($value);
        if (! $this->isValidUlid($value)) {
            return null;
        }

        return new Ulid($value);
    }

    private function isValidUlid(string $value): bool
    {
        return SymfonyUlid::isValid($value);
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
