<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Command;

use App\Core\Customer\Domain\Entity\CustomerInterface;
use App\Shared\Domain\Bus\Command\CommandInterface;

final class CreateCustomerCommand implements CommandInterface
{
    public function __construct(
        public readonly CustomerInterface $customer
    ) {
    }
}
