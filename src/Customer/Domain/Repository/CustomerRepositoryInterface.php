<?php

declare(strict_types=1);

namespace App\Customer\Domain\Repository;

use App\Customer\Domain\Entity\Customer;
use App\Customer\Domain\Entity\CustomerInterface;
use Doctrine\ODM\MongoDB\LockMode;

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
        int $lockMode = LockMode::NONE,
        ?int $lockVersion = null
    ): ?object;
}
