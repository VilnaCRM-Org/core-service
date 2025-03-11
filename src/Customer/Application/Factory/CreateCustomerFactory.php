<?php

declare(strict_types=1);

namespace App\Customer\Application\Factory;

use App\Customer\Application\Command\CreateCustomerCommand;
use App\Customer\Domain\Entity\CustomerStatus;
use App\Customer\Domain\Entity\CustomerType;

final class CreateCustomerFactory implements CreateCustomerFactoryInterface
{
    public function create(
        string $initials,
        string $email,
        string $phone,
        string $leadSource,
        CustomerType $type,
        CustomerStatus $status,
        bool $confirmed
    ): CreateCustomerCommand {
        return new CreateCustomerCommand(
            $initials,
            $email,
            $phone,
            $leadSource,
            $type,
            $status,
            $confirmed
        );
    }
}
