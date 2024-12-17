<?php

namespace App\Customer\Application\Command;

use App\Customer\Domain\Entity\CustomerInterface;
use App\Shared\Domain\Bus\Command\CommandResponseInterface;

class PutCustomerCommandResponse implements CommandResponseInterface
{
    public function __construct(public CustomerInterface $createdCustomer)
    {

    }
}