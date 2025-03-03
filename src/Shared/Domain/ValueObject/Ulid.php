<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

class Ulid implements UlidInterface
{
    private string $uid;

    public function __construct(string $uid)
    {
        $this->uid = $uid;
    }

    public function __toString(): string
    {
        return $this->uid;
    }
}
