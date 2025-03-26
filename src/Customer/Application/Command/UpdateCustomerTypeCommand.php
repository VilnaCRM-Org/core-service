<?php

declare(strict_types=1);

namespace App\Customer\Application\Command;

use App\Customer\Domain\Entity\CustomerType;
use App\Customer\Domain\ValueObject\CustomerTypeUpdate;
use App\Shared\Domain\Bus\Command\CommandInterface;

final readonly class UpdateCustomerTypeCommand implements CommandInterface
{
    public function __construct(
        public CustomerType $customerType,
        public CustomerTypeUpdate $update,
    ) {
    }
}
