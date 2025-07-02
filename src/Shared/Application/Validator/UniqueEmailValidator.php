<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class UniqueEmailValidator extends ConstraintValidator
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository
    ) {
    }

    /**
     * @param null|string $value
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $customer = $this->customerRepository->findByEmail($value);

        if ($customer !== null) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}
