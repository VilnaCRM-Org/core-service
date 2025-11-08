<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Factory;

use App\Core\Customer\Application\Transformer\CustomerRelationTransformerInterface;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
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
        return new CustomerUpdate(
            $this->resolveInitials($input, $customer),
            $this->resolveEmail($input, $customer),
            $this->resolvePhone($input, $customer),
            $this->resolveLeadSource($input, $customer),
            $this->resolveType($input, $customer),
            $this->resolveStatus($input, $customer),
            $this->resolveConfirmed($input, $customer)
        );
    }

    private function resolveInitials(array $input, Customer $customer): string
    {
        return $this->getStringValue($input['initials'] ?? null, $customer->getInitials());
    }

    private function resolveEmail(array $input, Customer $customer): string
    {
        return $this->getStringValue($input['email'] ?? null, $customer->getEmail());
    }

    private function resolvePhone(array $input, Customer $customer): string
    {
        return $this->getStringValue($input['phone'] ?? null, $customer->getPhone());
    }

    private function resolveLeadSource(array $input, Customer $customer): string
    {
        return $this->getStringValue($input['leadSource'] ?? null, $customer->getLeadSource());
    }

    private function resolveType(array $input, Customer $customer): CustomerType
    {
        return $this->relationResolver->resolveType($input['type'] ?? null, $customer);
    }

    private function resolveStatus(array $input, Customer $customer): CustomerStatus
    {
        return $this->relationResolver->resolveStatus($input['status'] ?? null, $customer);
    }

    private function resolveConfirmed(array $input, Customer $customer): bool
    {
        return $input['confirmed'] ?? $customer->isConfirmed();
    }

    /**
     * Returns the new value if it's not empty/whitespace-only, otherwise returns the default value.
     * This prevents GraphQL mutations from overwriting existing values with blank strings.
     */
    private function getStringValue(?string $newValue, string $defaultValue): string
    {
        return $this->hasValidContent($newValue) ? $newValue : $defaultValue;
    }

    private function hasValidContent(?string $value): bool
    {
        if ($value === null) {
            return false;
        }

        return strlen(trim($value)) > 0;
    }
}
