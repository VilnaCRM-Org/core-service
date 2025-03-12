<?php

declare(strict_types=1);

namespace App\Customer\Application\Factory;

use App\Customer\Application\Command\CreateTypeCommand;

final class CreateTypeFactory implements CreateTypeFactoryInterface
{
    public function create(string $value): CreateTypeCommand
    {
        return new CreateTypeCommand($value);
    }
}
