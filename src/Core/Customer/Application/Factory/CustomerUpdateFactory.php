<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Factory;

use App\Core\Customer\Application\Transformer\CustomerRelationTransformerInterface;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\ValueObject\CustomerUpdate;

final readonly class CustomerUpdateFactory implements
    CustomerUpdateFactoryInterface
{
    private CustomerUpdateScalarResolver $scalarResolver;

    public function __construct(
        private CustomerRelationTransformerInterface $relationResolver,
        ?CustomerUpdateScalarResolver $scalarResolver = null,
    ) {
        $this->scalarResolver = $scalarResolver
            ?? new CustomerUpdateScalarResolver();
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
        $stringFields = $this->scalarResolver->resolveStrings($customer, $input);

        return new CustomerUpdate(
            $stringFields['initials'],
            $stringFields['email'],
            $stringFields['phone'],
            $stringFields['leadSource'],
            $this->relationResolver->resolveType($input['type'] ?? null, $customer),
            $this->relationResolver->resolveStatus($input['status'] ?? null, $customer),
            $this->scalarResolver->resolveConfirmed($customer, $input)
        );
    }
}
