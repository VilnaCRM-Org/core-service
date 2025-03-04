<?php

declare(strict_types=1);

namespace App\Customer\Domain\Factory;

use App\Customer\Domain\Entity\CustomerType;
use App\Shared\Domain\ValueObject\UlidInterface;

interface CustomerTypeFactoryInterface
{
    public function create(
        string $value,
        UlidInterface $ulid
    ): CustomerType;
}
