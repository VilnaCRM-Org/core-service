<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Factory;

use App\Core\Customer\Application\Command\CreateStatusCommand;

final class CreateStatusFactory implements CreateStatusFactoryInterface
{
    public function create(string $value): CreateStatusCommand
    {
        return new CreateStatusCommand($value);
    }
}
