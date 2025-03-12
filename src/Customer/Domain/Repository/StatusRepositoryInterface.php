<?php

declare(strict_types=1);

namespace App\Customer\Domain\Repository;

use App\Customer\Domain\Entity\CustomerStatus;

interface StatusRepositoryInterface
{
    public function save(CustomerStatus $customerStatus): void;
}
