<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Transformer;

use ApiPlatform\Metadata\IriConverterInterface;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Exception\CustomerStatusNotFoundException;
use App\Core\Customer\Domain\Exception\CustomerTypeNotFoundException;

final readonly class CustomerRelationTransformer implements
    CustomerRelationTransformerInterface
{
    public function __construct(
        private IriConverterInterface $iriConverter,
    ) {
    }

    public function resolveType(
        ?string $typeIri,
        Customer $customer
    ): CustomerType {
        $iri = $typeIri ?? $this->getDefaultTypeIri($customer);

        $resource = $this->iriConverter->getResourceFromIri($iri);

        if (!$resource instanceof CustomerType) {
            throw CustomerTypeNotFoundException::withIri($iri);
        }

        return $resource;
    }

    public function resolveStatus(
        ?string $statusIri,
        Customer $customer
    ): CustomerStatus {
        $iri = $statusIri ?? $this->getDefaultStatusIri($customer);

        $resource = $this->iriConverter->getResourceFromIri($iri);

        if (!$resource instanceof CustomerStatus) {
            throw CustomerStatusNotFoundException::withIri($iri);
        }

        return $resource;
    }

    private function getDefaultTypeIri(Customer $customer): string
    {
        return sprintf(
            '/api/customer_types/%s',
            $customer->getType()->getUlid()
        );
    }

    private function getDefaultStatusIri(Customer $customer): string
    {
        return sprintf(
            '/api/customer_statuses/%s',
            $customer->getStatus()->getUlid()
        );
    }
}
