<?php

declare(strict_types=1);

namespace App\Customer\Domain\Entity;

use Symfony\Component\Uid\Ulid;

class CustomerType
{
    private Ulid $ulid;

    public function __construct(private string $value)
    {
        $this->ulid = new Ulid();
    }

    public function getUlid(): string
    {
        return (string) $this->ulid;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }
}
