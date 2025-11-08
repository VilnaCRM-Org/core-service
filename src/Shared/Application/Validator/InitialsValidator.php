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
        private readonly ValidationSkipChecker $skipChecker
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
        return is_string($value) && trim($value) === '';
    }

    private function addWhitespaceViolation(): void
    {
        $this->context
            ->buildViolation($this->translator->trans('initials.spaces'))
            ->addViolation();
    }
}
