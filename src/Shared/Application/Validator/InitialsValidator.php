<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

final class InitialsValidator extends ConstraintValidator
{
    public function __construct(
        private readonly TranslatorInterface $translator
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if ($this->shouldSkipValidation($value, $constraint)) {
            return;
        }

        if ($this->containsOnlySpaces($value)) {
            $this->addViolation(
                $this->translator->trans('initials.spaces')
            );
        }
    }

    private function shouldSkipValidation(
        mixed $value,
        Constraint $constraint
    ): bool {
        if ($value === null) {
            return true;
        }

        return $constraint->isOptional() && $value === '';
    }

    private function containsOnlySpaces(string $value): bool
    {
        if (strlen($value) === 0) {
            return false;
        }

        return trim($value) === '';
    }

    private function addViolation(string $message): void
    {
        $this->context->buildViolation($message)->addViolation();
    }
}
