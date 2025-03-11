<?php

declare(strict_types=1);

namespace App\Customer\Application\Factory;

use App\Customer\Application\Command\CreateCustomerTypeCommand;

interface CreateTypeFactoryInterface
{
    public function create(
        string $value
    ): CreateCustomerTypeCommand;
}
