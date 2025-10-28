<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Factory;

use App\Core\Customer\Application\Transformer\CustomerRelationTransformerInterface;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\ValueObject\CustomerUpdate;

final readonly class CustomerUpdateFactory implements
    CustomerUpdateFactoryInterface
{
    public function __construct(
        private CustomerRelationTransformerInterface $relationResolver,
    ) {
    }

    /**
     * @param array{
     *     initials?: string|null,
     *     email?: string|null,
     *     phone?: string|null,
     *     leadSource?: string|null,
     *     type?: string|null,
     *     status?: string|null,
     *     confirmed?: bool|null
     * } $input
     */
    public function create(Customer $customer, array $input): CustomerUpdate
    {
        $customerType = $this->relationResolver->resolveType(
            $input['type'] ?? null,
            $customer
        );

        $customerStatus = $this->relationResolver->resolveStatus(
            $input['status'] ?? null,
            $customer
        );

        return new CustomerUpdate(
            $input['initials'] ?? $customer->getInitials(),
            $input['email'] ?? $customer->getEmail(),
            $input['phone'] ?? $customer->getPhone(),
            $input['leadSource'] ?? $customer->getLeadSource(),
            $customerType,
            $customerStatus,
            $input['confirmed'] ?? $customer->isConfirmed()
        );
    }
}
