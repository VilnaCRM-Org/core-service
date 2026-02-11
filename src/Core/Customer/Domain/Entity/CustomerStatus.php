<?php

declare(strict_types=1);

namespace App\Core\Customer\Domain\Entity;

use App\Core\Customer\Domain\ValueObject\CustomerStatusUpdate;
use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Domain\ValueObject\UlidInterface;

class CustomerStatus implements CustomerStatusInterface
{
    public function __construct(
        private string $value,
        private mixed $ulid
    ) {
    }

    public function getUlid(): string
    {
        if ($this->ulid instanceof UlidInterface) {
            return (string) $this->ulid;
        }

        if (is_object($this->ulid) && method_exists($this->ulid, 'getData')) {
            return (string) Ulid::fromBinary($this->ulid->getData());
        }

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
