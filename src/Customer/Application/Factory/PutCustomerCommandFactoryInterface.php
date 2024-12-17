<?php

namespace App\Customer\Application\Factory;

use App\Customer\Application\Command\PutCustomerCommand;

interface PutCustomerCommandFactoryInterface
{
    public function create(
        string $customerId,
        ?string $initials,
        ?string $email,
        ?string $phone,
        ?string $leadSource,
        ?string $type,
        ?string $status,
    ): PutCustomerCommand;
}