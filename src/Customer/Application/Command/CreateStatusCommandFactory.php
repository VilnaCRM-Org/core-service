<?php

declare(strict_types=1);

namespace App\Customer\Application\Command;

use App\Shared\Domain\Bus\Command\CreateCustomerStatusCommand;

final class CreateStatusCommandFactory implements CreateStatusCommandFactoryInterface
{
    public function create(string $value): CreateCustomerStatusCommand
    {
        return new CreateCustomerStatusCommand($value);
    }
}
