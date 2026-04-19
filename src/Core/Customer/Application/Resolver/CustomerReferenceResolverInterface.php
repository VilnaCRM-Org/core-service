<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Resolver;

use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;

interface CustomerReferenceResolverInterface
{
    public function resolveType(string $idOrIri): CustomerType;

    public function resolveStatus(string $idOrIri): CustomerStatus;
}
