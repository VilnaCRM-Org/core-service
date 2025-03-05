<?php

declare(strict_types=1);

namespace App\Customer\Domain\Entity;

use App\Shared\Domain\ValueObject\UlidInterface;

class CustomerType
{
    public function __construct(private string $value, private UlidInterface $ulid)
    {
    }

    public function getUlid(): UlidInterface
    {
        return $this->ulid;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
