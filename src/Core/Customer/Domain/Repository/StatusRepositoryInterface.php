<?php

declare(strict_types=1);

namespace App\Core\Customer\Domain\Repository;

use App\Core\Customer\Domain\Entity\CustomerStatus;

interface StatusRepositoryInterface
{
    public function save(CustomerStatus $customerStatus): void;

    /**
     * @return CustomerStatus
     */
    public function find(
        mixed $id,
        int $lockMode = 0,
        ?int $lockVersion = null
    ): ?object;

    public function delete(CustomerStatus $customerStatus): void;

    public function deleteByValue(string $value): void;
}
