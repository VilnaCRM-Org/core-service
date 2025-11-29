<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator\Guard;

use Symfony\Component\Validator\Constraint;

/**
 * Guards against validating null and empty values.
 */
final class EmptyValueGuard
{
    public function shouldSkip(
        array|string|int|float|bool|null $value,
        Constraint $constraint
    ): bool {
        if ($this->isNullValue($value)) {
            return true;
        }

        if ($this->isEmptyString($value)) {
            return $this->shouldSkipEmptyString($constraint);
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

    private function shouldSkipEmptyString(Constraint $constraint): bool
    {
        $constraint->isOptional();

        return true;
    }
}
