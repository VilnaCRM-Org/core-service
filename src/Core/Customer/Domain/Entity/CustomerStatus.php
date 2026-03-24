<?php

declare(strict_types=1);

namespace App\Core\Customer\Domain\Entity;

use App\Core\Customer\Domain\ValueObject\CustomerStatusUpdate;
use App\Shared\Domain\ValueObject\UlidInterface;

class CustomerStatus implements CustomerStatusInterface
{
    private UlidInterface $ulid;

    public function __construct(
        private string $value,
        UlidInterface $ulid
    ) {
        $this->ulid = $ulid;
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

    public function update(CustomerStatusUpdate $updateData): void
    {
        $this->value = $updateData->value;
    }
}
