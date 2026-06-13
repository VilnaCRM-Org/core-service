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
     * @return Customer|null
     */
    public function find(
        mixed $id,
        int $lockMode = 0,
        ?int $lockVersion = null
    ): ?object;

    /**
     * Bypass cache layers and return the current persisted customer state.
     *
     * Write paths use this to avoid stale reads and cache lock contention
     * under hot update workloads.
     *
     * @return Customer|null
     */
    public function findFresh(
        mixed $id,
        int $lockMode = 0,
        ?int $lockVersion = null
    ): ?object;

    public function delete(Customer $customer): void;

    public function deleteByEmail(string $email): void;

    public function deleteById(mixed $id): void;
}
