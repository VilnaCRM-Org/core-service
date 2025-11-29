<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

use App\Shared\Application\Validator\Guard\EmptyValueGuard;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

final class InitialsValidator extends ConstraintValidator
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly EmptyValueGuard $skipChecker
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if ($this->skipChecker->shouldSkip($value, $constraint)) {
            return;
        }

        if ($this->isOnlyWhitespace($value)) {
            $this->addWhitespaceViolation();
        }
    }

    private function isOnlyWhitespace(array|string|int|float|bool|null $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        return trim($value) === '';
    }

    private function addWhitespaceViolation(): void
    {
        $this->context
            ->buildViolation($this->translator->trans('initials.spaces'))
            ->addViolation();
    }
}
