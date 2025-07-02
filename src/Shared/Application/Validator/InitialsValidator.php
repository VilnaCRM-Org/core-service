<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

final class InitialsValidator extends ConstraintValidator
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if ($value === null) {
            return;
        }

        if ($value === '') {
            return;
        }

        if (trim($value) === '') {
            $this->context
                ->buildViolation($this->translator->trans('initials.spaces'))
                ->addViolation();
        }
    }
}
