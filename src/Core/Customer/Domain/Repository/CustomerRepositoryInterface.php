<?php

declare(strict_types=1);

namespace App\Core\Customer\Domain\Repository;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerInterface;

interface CustomerRepositoryInterface
{
    public function save(Customer $customer): void;

    public function findByEmail(string $email): ?CustomerInterface;

    /**
     * @param string $id
     *
     * @return Customer
     */
    public function find(
        mixed $id,
        int $lockMode = 0,
        ?int $lockVersion = null
    ): ?object;

    public function delete(Customer $customer): void;
}
