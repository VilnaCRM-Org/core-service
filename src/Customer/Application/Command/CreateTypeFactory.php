<?php

declare(strict_types=1);

namespace App\Customer\Application\Command;

use App\Shared\Domain\Bus\Command\CreateCustomerTypeCommand;

final class CreateTypeFactory implements CreateTypeFactoryInterface
{
    public function create(string $value): CreateCustomerTypeCommand
    {
        return new CreateCustomerTypeCommand($value);
    }
}
