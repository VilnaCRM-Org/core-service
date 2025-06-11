<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Factory;

use App\Core\Customer\Application\Command\CreateCustomerCommand;
use App\Core\Customer\Domain\Entity\CustomerInterface;

interface CreateCustomerFactoryInterface
{
    public function create(
        CustomerInterface $customer,
    ): CreateCustomerCommand;
}
