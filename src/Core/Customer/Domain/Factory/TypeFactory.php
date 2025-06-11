<?php

declare(strict_types=1);

namespace App\Core\Customer\Domain\Factory;

use App\Core\Customer\Domain\Entity\CustomerType;
use App\Shared\Domain\ValueObject\UlidInterface;

final class TypeFactory implements TypeFactoryInterface
{
    public function create(string $value, UlidInterface $ulid): CustomerType
    {
        return new CustomerType($value, $ulid);
    }
}
