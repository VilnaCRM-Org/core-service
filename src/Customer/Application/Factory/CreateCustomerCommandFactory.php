<?php

declare(strict_types=1);

namespace App\Customer\Application\Factory;

use App\Customer\Application\Command\CreateCustomerCommand;

class CreateCustomerCommandFactory implements CreateCustomerCommandFactoryInterface
{
    public function create(
        string $initials,
        string $email,
        string $phone,
        string $leadSource,
        string $type,
        string $status
    ): CreateCustomerCommand {
        return new CreateCustomerCommand($initials, $email, $phone, $leadSource, $type, $status);
    }
}
