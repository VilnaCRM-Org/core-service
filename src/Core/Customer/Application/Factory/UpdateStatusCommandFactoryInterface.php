<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Factory;

use App\Core\Customer\Application\Command\UpdateCustomerStatusCommand;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\ValueObject\CustomerStatusUpdate;

interface UpdateStatusCommandFactoryInterface
{
    public function create(
        CustomerStatus $customerStatus,
        CustomerStatusUpdate $update
    ): UpdateCustomerStatusCommand;
}
