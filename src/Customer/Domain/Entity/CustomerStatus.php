<?php

declare(strict_types=1);

namespace App\Customer\Domain\Entity;

use App\Shared\Domain\ValueObject\UlidInterface;

class CustomerStatus
{
    public function __construct(
        private string $value,
        private UlidInterface $ulid
    ) {
    }

    public function getUlid(): string
    {
        return (string) $this->ulid;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
