<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Command;

use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\ValueObject\CustomerTypeUpdate;
use App\Shared\Domain\Bus\Command\CommandInterface;

final readonly class UpdateCustomerTypeCommand implements CommandInterface
{
    public function __construct(
        public CustomerType $customerType,
        public CustomerTypeUpdate $update,
    ) {
    }
}
