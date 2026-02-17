<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

final class UniqueEmailValidator extends ConstraintValidator
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly TranslatorInterface $translator
    ) {
    }

    #[Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if ($this->isEmailAlreadyUsed($value)) {
            $this->addViolation($this->translator->trans(
                'email.not.unique'
            ));
        }
    }

    private function isEmailAlreadyUsed(?string $value): bool
    {
        if ($value === null) {
            return false;
        }

        return $this->customerRepository->findByEmail($value) !== null;
    }

    private function addViolation(string $message): void
    {
        $this->context->buildViolation($message)
            ->addViolation();
    }
}
