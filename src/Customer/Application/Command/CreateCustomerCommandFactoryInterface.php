<?php

declare(strict_types=1);

namespace App\Customer\Application\Command;

use App\Customer\Domain\Entity\CustomerStatus;
use App\Customer\Domain\Entity\CustomerType;
use App\Shared\Domain\Bus\Command\CreateCustomerCommand;

interface CreateCustomerCommandFactoryInterface
{
    public function create(
        string $initials,
        string $email,
        string $phone,
        string $leadSource,
        CustomerType $type,
        CustomerStatus $status,
        bool $confirmed,
    ): CreateCustomerCommand;
}
