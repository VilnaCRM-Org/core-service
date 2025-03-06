<?php

declare(strict_types=1);

namespace App\Customer\Application\Command;

use App\Customer\Domain\Entity\CustomerStatus;
use App\Shared\Domain\Bus\Command\CommandResponseInterface;

class CreateStatusCommandResponse implements CommandResponseInterface
{
    public function __construct(public CustomerStatus $customerType)
    {
    }
}
