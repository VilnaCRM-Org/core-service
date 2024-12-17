<?php

namespace App\Customer\Application\Factory;

use App\Customer\Application\Command\DeleteCustomerCommand;

interface DeleteCustomerCommandFactoryInterface
{
    public function create(string $customerId);
}