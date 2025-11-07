<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Transformer;

use Symfony\Component\Uid\Ulid as SymfonyUlid;

/**
 * Validates ULID values before transformation.
 */
final class UlidValidator
{
    /**
     * @param array<mixed>|string|int|float|bool|object|null $value
     */
    public function isValid(array|string|int|float|bool|object|null $value): bool
    {
        return $value !== null && $this->isValidStringUlid($value);
    }

    /**
     * @param array<mixed>|string|int|float|bool|object|null $value
     */
    private function isValidStringUlid(array|string|int|float|bool|object|null $value): bool
    {
        return !is_string($value) || SymfonyUlid::isValid($value);
    }
}
