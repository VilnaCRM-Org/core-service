<?php

declare(strict_types=1);

namespace App\Customer\Domain\Repository;

use App\Customer\Domain\Entity\CustomerType;

interface TypeRepositoryInterface
{
    public function save(CustomerType $customerType): void;
}
