<?php

declare(strict_types=1);

namespace App\Customer\Domain\Factory;

use App\Customer\Domain\Entity\CustomerType;
use App\Shared\Domain\ValueObject\UlidInterface;

interface TypeFactoryInterface
{
    public function create(
        string $value,
        UlidInterface $ulid
    ): CustomerType;
}
