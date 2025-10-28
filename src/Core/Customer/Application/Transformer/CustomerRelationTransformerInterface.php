<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Transformer;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;

interface CustomerRelationTransformerInterface
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
