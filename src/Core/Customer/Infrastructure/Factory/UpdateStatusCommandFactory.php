<?php

declare(strict_types=1);

namespace App\Core\Customer\Infrastructure\Factory;

use App\Core\Customer\Application\Command\UpdateCustomerStatusCommand;
use App\Core\Customer\Application\Factory\UpdateStatusCommandFactoryInterface;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\ValueObject\CustomerStatusUpdate;

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
