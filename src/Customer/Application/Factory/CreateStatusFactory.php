<?php

declare(strict_types=1);

namespace App\Customer\Application\Factory;

use App\Customer\Application\Command\CreateStatusCommand;

final class CreateStatusFactory implements CreateStatusFactoryInterface
{
    public function create(string $value): CreateStatusCommand
    {
        return new CreateStatusCommand($value);
    }
}
