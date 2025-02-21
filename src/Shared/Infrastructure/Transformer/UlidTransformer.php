<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Transformer;

use MongoDB\BSON\Binary;
use Symfony\Component\Uid\Ulid;

final readonly class UlidTransformer
{
    public function toDatabase(mixed $value): ?Binary
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof Binary) {
            return $value;
        }

        $ulid = $this->convertToUlid($value);
        return new Binary($ulid->toBinary(), Binary::TYPE_GENERIC);
    }

    public function toPHP(mixed $value): ?Ulid
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof Ulid) {
            return $value;
        }

        return $this->convertToUlid($value);
    }

    private function convertToUlid(mixed $value): Ulid
    {
        if ($value instanceof Ulid) {
            return $value;
        }

        $string = $value instanceof Binary ? $value->getData() : $value;
        return Ulid::fromString($string);
    }
}
