<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Transformer;

use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\Factory\UlidFactory;
use InvalidArgumentException;
use MongoDB\BSON\Binary;
use Symfony\Component\Uid\Ulid as SymfonyUlid;

/**
 * Transforms between different Ulid type representations.
 */
final class UlidValueTransformer
{
    public function __construct(
        private readonly UlidFactory $ulidFactory,
        private readonly UlidRepresentationTransformer $representationTransformer,
        private readonly SymfonyUlidBinaryTransformer $symfonyUlidBinaryTransformer
    ) {
    }

    public function toUlid(mixed $value): Ulid
    {
        if ($value instanceof Ulid) {
            return $value;
        }

        $normalized = $this->representationTransformer->normalizeForUlidFactory($value);

        if (!is_string($normalized)) {
            throw new InvalidArgumentException(
                sprintf('Expected string after normalization, got %s', get_debug_type($normalized))
            );
        }

        return $this->ulidFactory->create($normalized);
    }

    public function fromBinary(Binary|string|SymfonyUlid $value): SymfonyUlid
    {
        return $this->symfonyUlidBinaryTransformer->fromBinary($value);
    }
}
