<?php

declare(strict_types=1);

namespace App\Customer\Application\Factory;

use App\Customer\Application\Command\UpdateCustomerTypeCommand;
use App\Customer\Domain\Entity\CustomerType;
use App\Customer\Domain\ValueObject\CustomerTypeUpdate;

interface UpdateCustomerTypeCommandFactoryInterface
{
    public function create(CustomerType $customerType, CustomerTypeUpdate $update): UpdateCustomerTypeCommand;
}
