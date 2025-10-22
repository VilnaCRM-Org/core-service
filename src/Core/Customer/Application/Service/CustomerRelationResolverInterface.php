<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Service;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;

interface CustomerRelationResolverInterface
{
    public function resolveType(
        ?string $typeIri,
        Customer $customer
    ): CustomerType;

    public function resolveStatus(
        ?string $statusIri,
        Customer $customer
    ): CustomerStatus;
}
