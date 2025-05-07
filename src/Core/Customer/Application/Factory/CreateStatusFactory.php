<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Factory;

use App\Core\Customer\Application\Command\CreateStatusCommand;
use App\Core\Customer\Domain\Entity\CustomerStatus;

final class CreateStatusFactory implements CreateStatusFactoryInterface
{
    public function create(CustomerStatus $status): CreateStatusCommand
    {
        return new CreateStatusCommand($status);
    }
}
