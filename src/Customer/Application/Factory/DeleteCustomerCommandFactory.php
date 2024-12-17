<?php

namespace App\Customer\Application\Factory;

use App\Customer\Application\Command\DeleteCustomerCommand;

class DeleteCustomerCommandFactory implements DeleteCustomerCommandFactoryInterface
{
    public function create(string $customerId ){
        return new DeleteCustomerCommand($customerId);
    }
}