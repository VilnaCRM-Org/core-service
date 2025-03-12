<?php

declare(strict_types=1);

namespace App\Customer\Domain\Factory;

use App\Customer\Domain\Entity\CustomerStatus;
use App\Shared\Domain\ValueObject\UlidInterface;

final class StatusFactory implements StatusFactoryInterface
{
    public function create(string $value, UlidInterface $ulid): CustomerStatus
    {
        return new CustomerStatus($value, $ulid);
    }
}
