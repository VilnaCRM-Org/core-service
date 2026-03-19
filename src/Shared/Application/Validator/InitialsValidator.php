<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Contracts\Translation\TranslatorInterface;

final class InitialsValidator extends ConstraintValidator
{
    public function __construct(
        private readonly TranslatorInterface $translator
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof Initials) {
            throw new UnexpectedTypeException($constraint, Initials::class);
        }

        if ($this->shouldSkipValidation($value)) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        if ($this->isOnlyWhitespace($value)) {
            $this->addWhitespaceViolation();
        }
    }

    private function shouldSkipValidation(mixed $value): bool
    {
        return $value === null;
    }

    private function isOnlyWhitespace(string $value): bool
    {
        return trim($value) === '';
    }

    private function addWhitespaceViolation(): void
    {
        $this->context
            ->buildViolation($this->translator->trans('initials.spaces'))
            ->addViolation();
    }
}
