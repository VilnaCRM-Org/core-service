<?php

declare(strict_types=1);

namespace App\Customer\Application\Command;

use App\Shared\Domain\Bus\Command\CreateCustomerStatusCommand;

interface CreateStatusFactoryInterface
{
    public function create(
        string $value
    ): CreateCustomerStatusCommand;
}
