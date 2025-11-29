<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Validator;

use Symfony\Component\Uid\Ulid as SymfonyUlid;

/**
 * Validates ULID values before transformation.
 */
final class UlidValidator
{
    public function isValid(array|string|int|float|bool|object|null $value): bool
    {
        if ($value === null) {
            return false;
        }

        return $this->isValidStringUlid($value);
    }

    private function isValidStringUlid(array|string|int|float|bool|object|null $value): bool
    {
        if (!is_string($value)) {
            return true;
        }

        return SymfonyUlid::isValid($value);
    }
}
