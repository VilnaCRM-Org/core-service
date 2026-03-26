<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Transformer;

use MongoDB\BSON\Binary;
use Symfony\Component\Uid\Ulid as SymfonyUlid;

final class SymfonyUlidBinaryTransformer
{
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
}
