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
        if (! $this->isUlidProperty($property)) {
            return false;
        }

        return is_string($rawValue);
    }

    private function isUlidProperty(string $property): bool
    {
        return str_ends_with($property, 'ulid');
    }

    private function parseUlidValue(string $value): Ulid|array|null
    {
        if (str_contains($value, '..')) {
            $parts = explode('..', $value, 2);
            $min = $this->createUlidIfValid(trim($parts[0]));
            $max = $this->createUlidIfValid(trim($parts[1]));
            if (! $min instanceof Ulid || ! $max instanceof Ulid) {
                return null;
            }

            return [$min, $max];
        }

        return $this->createUlidIfValid(trim($value));
    }

    private function createUlidIfValid(string $value): ?Ulid
    {
        if ($value === '' || ! SymfonyUlid::isValid($value)) {
            return null;
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
