<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Factory;

use App\Core\Customer\Application\Command\UpdateCustomerTypeCommand;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\ValueObject\CustomerTypeUpdate;

final readonly class UpdateTypeCommandFactory implements
    UpdateTypeCommandFactoryInterface
{
    public function create(
        CustomerType $customerType,
        CustomerTypeUpdate $update
    ): UpdateCustomerTypeCommand {
        return new UpdateCustomerTypeCommand($customerType, $update);
    }
}
