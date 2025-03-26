<?php

declare(strict_types=1);

namespace App\Customer\Application\Factory;

use App\Customer\Application\Command\UpdateCustomerCommand;
use App\Customer\Domain\Entity\Customer;
use App\Customer\Domain\ValueObject\CustomerUpdate;

interface UpdateCustomerCommandFactoryInterface
{
    public function create(
        Customer $customer,
        CustomerUpdate $updateData,
    ): UpdateCustomerCommand;
}
