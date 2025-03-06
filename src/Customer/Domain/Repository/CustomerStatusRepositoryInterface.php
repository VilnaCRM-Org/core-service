<?php

declare(strict_types=1);

namespace App\Customer\Domain\Repository;

interface CustomerStatusRepositoryInterface
{
    public function save(object $customerStatus): void;
}
