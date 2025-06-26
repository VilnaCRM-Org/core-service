<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Shared\Domain\ValueObject\Ulid;
use Faker\Provider\Base;
use Symfony\Component\Uid\Ulid as SymfonyUlid;

final class UlidProvider extends Base
{
    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function ulid(): Ulid
    {
        return new Ulid((string) new SymfonyUlid());
    }
}
