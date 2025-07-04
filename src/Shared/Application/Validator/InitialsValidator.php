<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

final class InitialsValidator extends ConstraintValidator
{
    private const MIN_LENGTH = 1;
    private const MAX_LENGTH = 255;
    private const INITIALS_PATTERN = '/^[A-Za-z]+(\s[A-Za-z]+)*$/';

    public function __construct(
        private TranslatorInterface $translator
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$this->isValidInput($value)) {
            return;
        }

        $this->checkInitials((string) $value);
    }

    private function isValidInput(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        if (!is_string($value)) {
            $this->addViolation('The value must be a string.');
            return false;
        }

        return true;
    }

    private function checkInitials(string $value): void
    {
        $trimmed = trim($value);

        if (!$this->hasValidContent($trimmed)) {
            return;
        }

        if (!$this->hasValidLength($trimmed)) {
            $this->addViolation('Invalid initials length.');
        }

        if (!$this->hasValidFormat($trimmed)) {
            $this->addViolation('Invalid initials format.');
        }
    }

    private function hasValidContent(string $value): bool
    {
        if ($value === '') {
            $message = $this->translator->trans('initials.spaces');
            $this->addViolation($message);
            return false;
        }

        return true;
    }

    private function hasValidLength(string $value): bool
    {
        $length = strlen($value);
        return $length >= self::MIN_LENGTH && $length <= self::MAX_LENGTH;
    }

    private function hasValidFormat(string $value): bool
    {
        return preg_match(self::INITIALS_PATTERN, $value) === 1;
    }

    private function addViolation(string $message): void
    {
        $this->context->buildViolation($message)->addViolation();
    }
}
