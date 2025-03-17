<?php

declare(strict_types=1);

namespace App\Customer\Domain\Repository;

use App\Customer\Domain\Entity\Customer;
use App\Customer\Domain\Entity\CustomerInterface;

interface CustomerRepositoryInterface
{
    public function save(Customer $customer): void;

    public function findByEmail(string $email): ?CustomerInterface;
}
