<?php

declare(strict_types=1);

namespace App\Customer\Infrastructure\Factory;

use App\Customer\Application\Command\UpdateCustomerStatusCommand;
use App\Customer\Application\Factory\UpdateStatusCommandFactoryInterface;
use App\Customer\Domain\Entity\CustomerStatus;
use App\Customer\Domain\ValueObject\CustomerStatusUpdate;

final readonly class UpdateStatusCommandFactory implements
    UpdateStatusCommandFactoryInterface
{
    public function create(
        CustomerStatus $customerStatus,
        CustomerStatusUpdate $update
    ): UpdateCustomerStatusCommand {
        return new UpdateCustomerStatusCommand($customerStatus, $update);
    }
}
