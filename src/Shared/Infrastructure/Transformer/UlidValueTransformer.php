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

        if ($value instanceof SymfonyUlid) {
            return $this->ulidFactory->create((string) $value);
        }

        if ($value instanceof Binary) {
            return $this->ulidFactory->create(
                (string) SymfonyUlid::fromBinary($value->getData())
            );
        }

        return $this->ulidFactory->create($value);
    }

    public function fromBinary(array|string|int|float|bool|object|null $value): SymfonyUlid
    {
        if ($value instanceof SymfonyUlid) {
            return $value;
        }

        if ($value instanceof Binary) {
            return SymfonyUlid::fromBinary($value->getData());
        }

        return SymfonyUlid::fromBinary($value);
    }
}
