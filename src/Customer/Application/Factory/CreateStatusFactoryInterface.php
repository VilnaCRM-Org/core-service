<?php

declare(strict_types=1);

namespace App\Customer\Application\Factory;

use App\Customer\Application\Command\CreateStatusCommand;

interface CreateStatusFactoryInterface
{
    public function create(
        string $value
    ): CreateStatusCommand;
}
