<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Transformer;

use InvalidArgumentException;
use MongoDB\BSON\Binary;
use Symfony\Component\Uid\Ulid as SymfonyUlid;

final class UlidRepresentationTransformer
{
    public function normalizeForUlidFactory(mixed $value): string|int|float|bool|null
    {
        if ($value instanceof SymfonyUlid) {
            return (string) $value;
        }

        if ($value instanceof Binary) {
            return (string) SymfonyUlid::fromBinary($value->getData());
        }

        if (is_object($value)) {
            throw new InvalidArgumentException(
                sprintf('Unsupported object type: %s', $value::class)
            );
        }

        return $value;
    }
}
