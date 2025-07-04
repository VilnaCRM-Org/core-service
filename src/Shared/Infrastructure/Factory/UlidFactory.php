<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Factory;

use App\Shared\Domain\Factory\UlidFactoryInterface;
use App\Shared\Domain\ValueObject\Ulid;
use Symfony\Component\Uid\Ulid as SymfonyUlid;

final class UlidFactory implements UlidFactoryInterface
{
    public function create(string $ulid = ''): Ulid
    {
        if ($ulid === '') {
            $ulid = SymfonyUlid::generate();
        }

        return new Ulid((string) $ulid);
    }
}
