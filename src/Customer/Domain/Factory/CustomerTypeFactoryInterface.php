<?php

declare(strict_types=1);

namespace App\Customer\Domain\Factory;

use App\Customer\Domain\Entity\CustomerType;
use App\Shared\Domain\ValueObject\Ulid;

interface CustomerTypeFactoryInterface
{
    public function create(
        string $value,
        Ulid $id
    ): CustomerType;
}
