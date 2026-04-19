<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

final class UniqueEmailValidator extends ConstraintValidator
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly TranslatorInterface $translator,
        private readonly RequestStack $requestStack,
    ) {
    }

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

        $customer = $this->customerRepository->findByEmail($value);

        if (! $customer instanceof Customer) {
            return false;
        }

        $currentCustomerUlid = $this->getCurrentCustomerUlid();

        return $currentCustomerUlid === null
            || $customer->getUlid() !== $currentCustomerUlid;
    }

    private function addViolation(string $message): void
    {
        $this->context->buildViolation($message)
            ->addViolation();
    }

    private function getCurrentCustomerUlid(): ?string
    {
        $ulid = $this->requestStack
            ->getCurrentRequest()
            ?->attributes
            ->get('ulid');

        return is_string($ulid) && $ulid !== ''
            ? $ulid
            : null;
    }
}
