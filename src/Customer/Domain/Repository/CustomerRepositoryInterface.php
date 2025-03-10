<?php

declare(strict_types=1);

namespace App\Customer\Domain\Repository;

interface CustomerRepositoryInterface
{
    public function save(object $customer): void;
}
