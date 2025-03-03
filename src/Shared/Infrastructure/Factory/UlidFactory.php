<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Factory;

use App\Shared\Domain\Factory\UlidFactoryInterface;
use App\Shared\Domain\ValueObject\Ulid;

final class UlidFactory implements UlidFactoryInterface
{
    public function create(string $uuid): Ulid
    {
        return new Ulid($uuid);
    }
}
