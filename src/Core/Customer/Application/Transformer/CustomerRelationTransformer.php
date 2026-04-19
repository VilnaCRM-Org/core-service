<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Transformer;

use App\Core\Customer\Application\Resolver\CustomerReferenceResolver;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;

final readonly class CustomerRelationTransformer implements
    CustomerRelationTransformerInterface
{
    public function __construct(
        private CustomerReferenceResolver $referenceResolver,
    ) {
    }

    public function resolveType(
        ?string $typeIri,
        Customer $customer
    ): CustomerType {
        if ($typeIri === null) {
            return $this->referenceResolver->resolveType($customer->getType()->getUlid());
        }

        return $this->referenceResolver->resolveType($typeIri);
    }

    public function resolveStatus(
        ?string $statusIri,
        Customer $customer
    ): CustomerStatus {
        if ($statusIri === null) {
            return $this->referenceResolver->resolveStatus($customer->getStatus()->getUlid());
        }

        return $this->referenceResolver->resolveStatus($statusIri);
    }
}
