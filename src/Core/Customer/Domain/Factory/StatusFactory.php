<?php

declare(strict_types=1);

namespace App\Core\Customer\Domain\Factory;

use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Shared\Domain\ValueObject\UlidInterface;

final class StatusFactory implements StatusFactoryInterface
{
    #[\Override]
    public function create(string $value, UlidInterface $ulid): CustomerStatus
    {
        return new CustomerStatus($value, $ulid);
    }
}
