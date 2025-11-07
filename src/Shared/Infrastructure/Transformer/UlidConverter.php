<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Transformer;

use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\Factory\UlidFactory;
use Symfony\Component\Uid\Ulid as SymfonyUlid;

/**
 * Converts values to Ulid domain objects.
 */
final class UlidConverter
{
    public function __construct(
        private readonly UlidFactory $ulidFactory
    ) {
    }

    /**
     * @param array<mixed>|string|int|float|bool|object|null $value
     */
    public function toUlid(array|string|int|float|bool|object|null $value): Ulid
    {
        return $value instanceof Ulid
            ? $value
            : $this->ulidFactory->create($value);
    }

    /**
     * @param array<mixed>|string|int|float|bool|object|null $binary
     */
    public function fromBinary(array|string|int|float|bool|object|null $binary): SymfonyUlid
    {
        return $binary instanceof SymfonyUlid
            ? $binary
            : SymfonyUlid::fromBinary($binary);
    }
}
