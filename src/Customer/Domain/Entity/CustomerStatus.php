<?php

declare(strict_types=1);

namespace App\Customer\Domain\Entity;

use Symfony\Component\Uid\Ulid;

class CustomerStatus
{
    private Ulid $ulid;

    public function __construct(private string $value)
    {
        $this->ulid = new Ulid();
    }

    public function getUlid(): Ulid
    {
        return $this->ulid;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
