<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use Faker\Provider\Base;
use Symfony\Component\Uid\Ulid;

final class UlidProvider extends Base
{
    /** @psalm-suppress PossiblyUnusedMethod */
    public function ulid(): Ulid
    {
        return new Ulid();
    }
}
