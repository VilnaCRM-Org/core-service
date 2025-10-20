<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Factory;

use App\Core\Customer\Application\Command\UpdateCustomerCommand;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\ValueObject\CustomerUpdate;

interface UpdateCustomerCommandFactoryInterface
{
    public function create(
        Customer $customer,
        CustomerUpdate $updateData,
    ): UpdateCustomerCommand;
}
