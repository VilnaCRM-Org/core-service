<?php

declare(strict_types=1);

namespace App\Customer\Application\Command;

use App\Customer\Domain\Entity\CustomerInterface;
use App\Shared\Domain\Bus\Command\CommandResponseInterface;

class CreateCustomerCommandResponse implements CommandResponseInterface
{
    public function __construct(public CustomerInterface $createdCustomer)
    {

    }
}
