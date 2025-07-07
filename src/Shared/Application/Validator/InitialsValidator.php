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
        if ($constraint->isOptional() && $this->isEmpty($value)) {
            return;
        }

        if ($value === null) {
            return;
        }

        if (!is_string($value)) {
            return;
        }

        $trimmedValue = trim($value);

        if ($this->isEmpty($trimmedValue) && strlen($value) > 0) {
            $this->addViolation(
                $this->translator->trans('initials.spaces')
            );
        }
    }

    private function isEmpty(mixed $value): bool
    {
        return $value === '' || $value === null;
    }

    private function addViolation(string $message): void
    {
        $this->context->buildViolation($message)->addViolation();
    }
}
