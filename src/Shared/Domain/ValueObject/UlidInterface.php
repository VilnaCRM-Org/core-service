<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

interface UlidInterface
{
    public function __toString(): string;
}
