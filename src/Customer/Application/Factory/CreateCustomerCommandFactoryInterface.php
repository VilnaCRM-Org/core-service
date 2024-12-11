<?php

declare(strict_types=1);

namespace App\Customer\Application\Factory;

use App\Customer\Application\Command\CreateCustomerCommand;

interface CreateCustomerCommandFactoryInterface
{
    public function create(
        string $initials,
        string $email,
        string $phone,
        string $leadSource,
        string $type,
        string $status,
    ): CreateCustomerCommand;
}
