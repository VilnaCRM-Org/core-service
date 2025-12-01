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
            $this->getStringValue($input['initials'] ?? null, $customer->getInitials()),
            $this->getStringValue($input['email'] ?? null, $customer->getEmail()),
            $this->getStringValue($input['phone'] ?? null, $customer->getPhone()),
            $this->getStringValue($input['leadSource'] ?? null, $customer->getLeadSource()),
            $customerType,
            $customerStatus,
            $input['confirmed'] ?? $customer->isConfirmed()
        );
    }

    /**
     * Returns the new value if it's not empty/whitespace-only, otherwise returns the default value.
     * This prevents GraphQL mutations from overwriting existing values with blank strings.
     */
    private function getStringValue(?string $newValue, string $defaultValue): string
    {
        return $newValue !== null && trim($newValue) !== '' ? $newValue : $defaultValue;
    }
}
