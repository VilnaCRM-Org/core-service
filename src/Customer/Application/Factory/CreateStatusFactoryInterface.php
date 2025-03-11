<?php

declare(strict_types=1);

namespace App\Customer\Application\Factory;

use App\Customer\Application\Command\CreateCustomerStatusCommand;

interface CreateStatusFactoryInterface
{
    public function create(
        string $value
    ): CreateCustomerStatusCommand;
}
