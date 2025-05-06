<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Command;

use App\Core\Customer\Domain\Entity\CustomerType;
use App\Shared\Domain\Bus\Command\CommandResponseInterface;

final readonly class CreateTypeCommandResponse implements
    CommandResponseInterface
{
    public function __construct(public CustomerType $customerType)
    {
    }
}
