<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Transformer;

use MongoDB\BSON\Binary;
use Symfony\Component\Uid\Ulid;

final readonly class UlidTransformer
{
    public function transformFromString($value): ?Ulid
    {
        if ($value instanceof Binary) {
            return Ulid::fromRfc4122($value->getData());
        }
        return $value ? Ulid::fromString($value) : null;
    }

    public function transformFromSymfonyUlid($value): ?Binary
    {
        return $value instanceof Ulid
            ? new Binary($value->toBinary(), Binary::TYPE_GENERIC)
            : null;
    }
}
