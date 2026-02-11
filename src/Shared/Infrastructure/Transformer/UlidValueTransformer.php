<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Transformer;

use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\Factory\UlidFactory;
use MongoDB\BSON\Binary;
use Symfony\Component\Uid\Ulid as SymfonyUlid;

/**
 * Transforms between different Ulid type representations.
 */
final class UlidValueTransformer
{
    public function __construct(
        private readonly UlidFactory $ulidFactory
    ) {
    }

    public function toUlid(array|string|int|float|bool|object|null $value): Ulid
    {
        if ($value instanceof Ulid) {
            return $value;
        }

        return $this->ulidFactory->create($this->normalizeValue($value));
    }

    public function fromBinary(Binary|string|SymfonyUlid $value): SymfonyUlid
    {
        if ($value instanceof SymfonyUlid) {
            return $value;
        }

        if ($value instanceof Binary) {
            return SymfonyUlid::fromBinary($value->getData());
        }

        return SymfonyUlid::fromBinary($value);
    }

    private function normalizeValue(
        array|string|int|float|bool|object|null $value
    ): array|string|int|float|bool|null {
        if ($value instanceof SymfonyUlid) {
            return (string) $value;
        }

        if ($value instanceof Binary) {
            return (string) SymfonyUlid::fromBinary($value->getData());
        }

        return $value;
    }
}
