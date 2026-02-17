<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Factory;

use App\Core\Customer\Application\Command\CreateStatusCommand;
use App\Core\Customer\Domain\Entity\CustomerStatusInterface;

final class CreateStatusFactory implements CreateStatusFactoryInterface
{
    #[\Override]
    public function create(CustomerStatusInterface $status): CreateStatusCommand
    {
        return new CreateStatusCommand($status);
    }
}
