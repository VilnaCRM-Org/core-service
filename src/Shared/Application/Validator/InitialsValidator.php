<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

final class InitialsValidator extends ConstraintValidator
{
    private const MAX_LENGTH = 255;
    private const PATTERN = '/^[A-Za-z]+(\s[A-Za-z]+)*$/';

    public function __construct(
        private readonly TranslatorInterface $translator
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if ($this->shouldSkipValidation($value)) {
            return;
        }

        $this->validateInitials((string) $value);
    }

    private function shouldSkipValidation(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if (!is_string($value)) {
            $this->addError('The value must be a string.');
            return true;
        }

        return false;
    }

    private function validateInitials(string $value): void
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            $this->addError($this->translator->trans('initials.spaces'));
            return;
        }

        $this->validateLength($trimmed);
        $this->validatePattern($trimmed);
    }

    private function validateLength(string $value): void
    {
        if (strlen($value) > self::MAX_LENGTH) {
            $this->addError('Invalid initials length.');
        }
    }

    private function validatePattern(string $value): void
    {
        if (!preg_match(self::PATTERN, $value)) {
            $this->addError('Invalid initials format.');
        }
    }

    private function addError(string $message): void
    {
        $this->context->buildViolation($message)->addViolation();
    }
}
