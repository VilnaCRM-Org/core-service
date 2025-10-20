<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Command;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\ValueObject\CustomerUpdate;
use App\Shared\Domain\Bus\Command\CommandInterface;

final readonly class UpdateCustomerCommand implements CommandInterface
{
    public function __construct(
        public Customer $customer,
        public CustomerUpdate $updateData,
    ) {
    }
}
