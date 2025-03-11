<?php

declare(strict_types=1);

namespace App\Customer\Application\Command;

use App\Customer\Domain\Entity\CustomerStatus;
use App\Customer\Domain\Entity\CustomerType;
use App\Shared\Domain\Bus\Command\CreateCustomerCommand;

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
