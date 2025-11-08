<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Determines whether validation should be skipped for initials.
 */
final class ValidationSkipChecker
{
    public function shouldSkip(
        array|string|int|float|bool|null $value,
        Constraint $constraint
    ): bool {
        if ($this->isNullValue($value)) {
            return true;
        }

        if ($this->isEmptyString($value)) {
            return $this->shouldSkipEmptyString($value, $constraint);
        }

        return false;
    }

    private function isNullValue(array|string|int|float|bool|null $value): bool
    {
        return $value === null;
    }

    private function isEmptyString(array|string|int|float|bool|null $value): bool
    {
        return $value === '';
    }

    private function shouldSkipEmptyString(
        array|string|int|float|bool|null $value,
        Constraint $constraint
    ): bool {
        return $constraint->isOptional() || $value === '';
    }
}
