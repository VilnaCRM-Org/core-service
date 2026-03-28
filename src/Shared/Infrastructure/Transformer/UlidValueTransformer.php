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

        return $this->ulidFactory->create(
            $this->representationTransformer->normalizeForUlidFactory($value)
        );
    }

    public function fromBinary(Binary|string|SymfonyUlid $value): SymfonyUlid
    {
        return $this->symfonyUlidBinaryTransformer->fromBinary($value);
    }
}
