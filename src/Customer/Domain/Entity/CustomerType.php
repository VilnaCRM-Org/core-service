<?php

namespace App\Customer\Domain\Entity;

use App\Shared\Domain\ValueObject\UuidInterface;

class CustomerType
{
    public function __construct(private string $id, private string $value)
    {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}