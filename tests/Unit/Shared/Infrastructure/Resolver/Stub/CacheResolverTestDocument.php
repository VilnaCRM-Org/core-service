<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Resolver\Stub;

final readonly class CacheResolverTestDocument
{
    public function __construct(
        private string $id
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }
}
