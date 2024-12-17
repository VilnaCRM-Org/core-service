<?php

declare(strict_types=1);

namespace App\Customer\Domain\Repository;

use App\Customer\Domain\Entity\Customer;
use Doctrine\ODM\MongoDB\LockMode;

interface CustomerRepositoryInterface
{
    /**
     * @param Customer $customer
     */
    public function save(object $customer): void;

    /**
     * @param Customer $customer
     */
    public function delete(object $customer): void;

    /**
     * @param string $id
     *
     * @return Customer
     */
    public function find(string $id, int $lockMode = LockMode::NONE, ?int $lockVersion = null): ?object;
}
