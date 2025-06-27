<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Filter;

use ApiPlatform\Doctrine\Odm\Filter\FilterInterface;
use ApiPlatform\Metadata\Operation;
use App\Shared\Domain\ValueObject\Ulid;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use InvalidArgumentException;
use Symfony\Component\Uid\Ulid as SymfonyUlid;

/**
 * @psalm-suppress UndefinedClass
 */
final class UlidFilterProcessor implements FilterInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getDescription(string $resourceClass): array
    {
        return [];
    }

    /**
     * @param array<string, mixed> $context
     * @param object $queryNameGenerator
     *
     * @psalm-suppress UndefinedClass
     */
    public function apply(
        Builder $aggregationBuilder,
        object $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        $filters = $context['filters'] ?? [];

        foreach ($filters as $property => $value) {
            if ($this->isUlidProperty($property) &&
                $this->isValidUlid($value)) {
                $this->applyUlidFilter(
                    $aggregationBuilder,
                    $property,
                    $value
                );
            }
        }
    }

    private function isUlidProperty(string $property): bool
    {
        return str_ends_with($property, 'Id') ||
            str_ends_with($property, '_id') ||
            $property === 'id';
    }

    /**
     * @param mixed $value
     */
    private function isValidUlid($value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        return SymfonyUlid::isValid($value);
    }

    private function applyUlidFilter(
        Builder $aggregationBuilder,
        string $property,
        string $value
    ): void {
        try {
            $ulid = Ulid::fromString($value);
            $aggregationBuilder->match()->field($property)->equals($ulid);
        } catch (InvalidArgumentException $exception) {
            // Log or handle invalid ULID appropriately
            error_log('Invalid ULID provided: ' . $exception->getMessage());
        }
    }
}
