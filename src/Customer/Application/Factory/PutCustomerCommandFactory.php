<?php

namespace App\Customer\Application\Factory;

use App\Customer\Application\Command\PutCustomerCommand;

class PutCustomerCommandFactory implements PutCustomerCommandFactoryInterface
{
    public function create(string $customerId, ?string $initials, ?string $email, ?string $phone, ?string $leadSource, ?string $type, ?string $status): PutCustomerCommand
    {
        return new PutCustomerCommand($customerId, $initials, $email, $phone, $leadSource, $type, $status);
    }

}