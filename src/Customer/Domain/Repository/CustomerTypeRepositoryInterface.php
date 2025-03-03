<?php

declare(strict_types=1);

namespace App\Customer\Domain\Repository;

interface CustomerTypeRepositoryInterface
{
    public function save(object $customerType): void;
}
