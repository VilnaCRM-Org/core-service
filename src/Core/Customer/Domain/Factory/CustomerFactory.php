<?php

declare(strict_types=1);

namespace App\Core\Customer\Domain\Factory;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Shared\Domain\ValueObject\UlidInterface;

final class CustomerFactory implements CustomerFactoryInterface
{
    public function create(
        string $initials,
        string $email,
        string $phone,
        string $leadSource,
        CustomerType $type,
        CustomerStatus $status,
        bool $confirmed,
        UlidInterface $ulid
    ): Customer {
        return new Customer(
            $initials,
            $email,
            $phone,
            $leadSource,
            $type,
            $status,
            $confirmed,
            $ulid
        );
    }
}
