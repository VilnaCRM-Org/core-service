<?php

namespace App\Customer\Domain\Factory;

use App\Customer\Domain\Entity\CustomerInterface;
use App\Shared\Domain\ValueObject\Uuid;

interface CustomerFactoryInterface
{
    public function create(
        Uuid $id,
        string $initials,
        string $email,
        string $phone,
        string $leadSource,
        string $type,
        string $status
    ): CustomerInterface;
}
