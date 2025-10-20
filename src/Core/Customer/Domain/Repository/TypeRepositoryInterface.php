<?php

declare(strict_types=1);

namespace App\Core\Customer\Domain\Repository;

use App\Core\Customer\Domain\Entity\CustomerType;

interface TypeRepositoryInterface
{
    public function save(CustomerType $customerType): void;

    /**
     * @param string $id
     *
     * @return CustomerType
     */
    public function find(
        mixed $id,
        int $lockMode = 0,
        ?int $lockVersion = null
    ): ?object;

    public function delete(CustomerType $customerType): void;

    public function deleteByValue(string $value): void;
}
