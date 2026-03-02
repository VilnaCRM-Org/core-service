<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Transformer;

use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\Factory\UlidFactory;
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
        return $value instanceof Ulid
            ? $value
            : $this->ulidFactory->create($value);
    }

    public function fromBinary(array|string|int|float|bool|object|null $value): SymfonyUlid
    {
        return $value instanceof SymfonyUlid
            ? $value
            : SymfonyUlid::fromBinary($value);
    }
}
