<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Validator;

use App\Shared\Domain\ValueObject\Ulid;
use Symfony\Component\Uid\Ulid as SymfonyUlid;

/**
 * Validates ULID values.
 */
final class UlidValidator
{
    public function isValid(array|string|int|float|bool|object|null $value): bool
    {
        // Domain Ulid objects and SymfonyUlid objects are already valid
        return ($value instanceof Ulid || $value instanceof SymfonyUlid)
            || (is_string($value) && SymfonyUlid::isValid($value));
    }
}
