<?php

namespace App\Customer\Application\Command;

use App\Shared\Domain\Bus\Command\CommandInterface;

class DeleteCustomerCommand implements CommandInterface
{
    public function __construct(public string $id)
    {}

}