<?php

declare(strict_types=1);

namespace App\Core\Customer\Domain\Entity;

use ApiPlatform\Metadata\ApiProperty;
use App\Shared\Domain\ValueObject\UlidInterface;

class CustomerStatus implements CustomerStatusInterface
{
    public function __construct(
        private string $value,
        private UlidInterface $ulid
    ) {
    }

    #[ApiProperty(readable: true, writable: false)]
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
