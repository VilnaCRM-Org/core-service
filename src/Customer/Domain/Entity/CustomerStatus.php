<?php

namespace App\Customer\Domain\Entity;

class CustomerStatus
{
    public function __construct(private string $value, private ?string $id = null)
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