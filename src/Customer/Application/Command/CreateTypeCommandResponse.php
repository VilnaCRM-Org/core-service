<?php

declare(strict_types=1);

namespace App\Customer\Application\Command;

use App\Customer\Domain\Entity\CustomerType;
use App\Shared\Domain\Bus\Command\CommandResponseInterface;

final class CreateTypeCommandResponse implements CommandResponseInterface
{
    public function __construct(public CustomerType $customerType)
    {
    }
}
