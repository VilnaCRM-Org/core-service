<?php

declare(strict_types=1);

namespace App\Customer\Application\Command;

use App\Shared\Domain\Bus\Command\CreateCustomerTypeCommand;

interface CreateTypeCommandFactoryInterface
{
    public function create(
        string $value
    ): CreateCustomerTypeCommand;
}
