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
        $currentType = $customer->getType();

        if ($typeIri === null) {
            return $currentType;
        }

        if ($this->matchesCurrentReference($typeIri, $currentType->getUlid())) {
            return $currentType;
        }

        return $this->referenceResolver->resolveType($typeIri);
    }

    public function resolveStatus(
        ?string $statusIri,
        Customer $customer
    ): CustomerStatus {
        $currentStatus = $customer->getStatus();

        if ($statusIri === null) {
            return $currentStatus;
        }

        if ($this->matchesCurrentReference($statusIri, $currentStatus->getUlid())) {
            return $currentStatus;
        }

        return $this->referenceResolver->resolveStatus($statusIri);
    }

    private function matchesCurrentReference(string $idOrIri, string $currentUlid): bool
    {
        return $idOrIri === $currentUlid
            || str_ends_with($idOrIri, '/' . $currentUlid);
    }
}
