<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator\Guard;

use Symfony\Component\Validator\Constraint;

final class EmptyValueGuard
{
    public static function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '';
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function shouldSkip(
        mixed $value,
        Constraint $_constraint
    ): bool {
        return $value === null;
    }
}
