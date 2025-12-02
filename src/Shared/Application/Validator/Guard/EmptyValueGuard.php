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
        return $value === null || $value === '';
    }
}
