<?php

declare(strict_types=1);

namespace App\Shared\Domain\Factory;

use App\Shared\Domain\ValueObject\Ulid;

interface UlidFactoryInterface
{
    public function create(string $ulid = ''): Ulid;
}
