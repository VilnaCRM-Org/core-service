<?php

declare(strict_types=1);

namespace App\Customer\Infrastructure\Factory;

use App\Customer\Application\Command\UpdateCustomerTypeCommand;
use App\Customer\Application\Factory\UpdateCustomerTypeCommandFactoryInterface;
use App\Customer\Domain\Entity\CustomerType;
use App\Customer\Domain\ValueObject\CustomerTypeUpdate;

final readonly class UpdateCustomerTypeCommandFactory implements
    UpdateCustomerTypeCommandFactoryInterface
{
    public function create(CustomerType $customerType, CustomerTypeUpdate $update): UpdateCustomerTypeCommand
    {
        return new UpdateCustomerTypeCommand($customerType, $update);
    }
}
