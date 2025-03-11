<?php

declare(strict_types=1);

namespace App\Customer\Domain\Repository;

interface TypeRepositoryInterface
{
    public function save(object $customerType): void;
}
