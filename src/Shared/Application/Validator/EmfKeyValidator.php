<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

use App\Shared\Application\Validator\Guard\EmptyValueGuard;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates AWS CloudWatch EMF dimension keys.
 */
final class EmfKeyValidator extends ConstraintValidator
{
    private const string CONTROL_CHARS_PATTERN = '/[\x00-\x1F\x7F]/';

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof EmfKey) {
            throw new UnexpectedTypeException($constraint, EmfKey::class);
        }

        if (EmptyValueGuard::isEmpty($value)) {
            $this->addViolation($constraint->emptyMessage);

            return;
        }

        $this->validateNotWhitespaceOnly($value, $constraint);
        $this->validateLength($value, $constraint);
        $this->validateAscii($value, $constraint);
        $this->validateNoControlChars($value, $constraint);
        $this->validateNotStartsWithColon($value, $constraint);
    }

    private function validateNotWhitespaceOnly(string $value, EmfKey $constraint): void
    {
        if (trim($value) === '') {
            $this->addViolation($constraint->emptyMessage);
        }
    }

    private function validateLength(string $value, EmfKey $constraint): void
    {
        if (strlen($value) > EmfKey::MAX_LENGTH) {
            $this->addViolation($constraint->tooLongMessage);
        }
    }

    private function validateAscii(string $value, EmfKey $constraint): void
    {
        if (!mb_check_encoding($value, 'ASCII')) {
            $this->addViolation($constraint->nonAsciiMessage);
        }
    }

    private function validateNoControlChars(string $value, EmfKey $constraint): void
    {
        if (preg_match(self::CONTROL_CHARS_PATTERN, $value) === 1) {
            $this->addViolation($constraint->controlCharsMessage);
        }
    }

    private function validateNotStartsWithColon(string $value, EmfKey $constraint): void
    {
        if (str_starts_with($value, ':')) {
            $this->addViolation($constraint->startsWithColonMessage);
        }
    }

    private function addViolation(string $message): void
    {
        $this->context->buildViolation($message)->addViolation();
    }
}
