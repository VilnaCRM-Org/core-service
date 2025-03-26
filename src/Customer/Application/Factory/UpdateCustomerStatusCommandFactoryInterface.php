<?php

declare(strict_types=1);

namespace App\Customer\Application\Factory;

use App\Customer\Application\Command\UpdateCustomerStatusCommand;
use App\Customer\Domain\Entity\CustomerStatus;
use App\Customer\Domain\ValueObject\CustomerStatusUpdate;

interface UpdateCustomerStatusCommandFactoryInterface
{
    public function create(CustomerStatus $customerStatus, CustomerStatusUpdate $update): UpdateCustomerStatusCommand;
}
