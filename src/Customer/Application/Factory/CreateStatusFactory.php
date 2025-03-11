<?php

declare(strict_types=1);

namespace App\Customer\Application\Factory;

use App\Customer\Application\Command\CreateCustomerStatusCommand;

final class CreateStatusFactory implements CreateStatusFactoryInterface
{
    public function create(string $value): CreateCustomerStatusCommand
    {
        return new CreateCustomerStatusCommand($value);
    }
}
