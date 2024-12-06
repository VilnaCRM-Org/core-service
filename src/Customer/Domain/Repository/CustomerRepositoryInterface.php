<?php

declare(strict_types=1);

namespace App\Customer\Domain\Repository;

use App\Customer\Domain\Entity\Customer;

interface CustomerRepositoryInterface
{
    /**
     * @param Customer $customer
     */
    public function save(object $customer): void;

    /**
     * @param Customer $user
     */
    public function delete(object $user): void;

    /**
     * @param string $id
     *
     * @return Customer
     */
    public function find(
        mixed $id,
        ?int $lockMode = null,
        ?int $lockVersion = null
    ): ?object;
}
