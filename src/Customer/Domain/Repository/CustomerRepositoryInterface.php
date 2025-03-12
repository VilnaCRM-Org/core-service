<?php

declare(strict_types=1);

namespace App\Customer\Domain\Repository;

use App\Customer\Domain\Entity\Customer;

interface CustomerRepositoryInterface
{
    public function save(Customer $customer): void;
}
