<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Factory;

use App\Core\Customer\Application\Command\CreateStatusCommand;

interface CreateStatusFactoryInterface
{
    public function create(
        string $value
    ): CreateStatusCommand;
}
