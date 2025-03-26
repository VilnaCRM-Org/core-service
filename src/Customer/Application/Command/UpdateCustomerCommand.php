<?php

declare(strict_types=1);

namespace App\Customer\Application\Command;

use App\Customer\Domain\Entity\Customer;
use App\Customer\Domain\ValueObject\CustomerUpdate;
use App\Shared\Domain\Bus\Command\CommandInterface;

final readonly class UpdateCustomerCommand implements CommandInterface
{
    public function __construct(
        public Customer $customer,
        public CustomerUpdate $updateData,
    ) {
    }
}
