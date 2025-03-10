<?php

declare(strict_types=1);

namespace App\Customer\Application\Command;

use App\Customer\Domain\Entity\Customer;
use App\Shared\Domain\Bus\Command\CommandResponseInterface;

final class CreateCustomerCommandResponse implements CommandResponseInterface
{
    public function __construct(public Customer $customer)
    {
    }
}
