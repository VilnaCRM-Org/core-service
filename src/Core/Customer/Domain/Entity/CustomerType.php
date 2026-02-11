<?php

declare(strict_types=1);

namespace App\Core\Customer\Domain\Entity;

use App\Core\Customer\Domain\ValueObject\CustomerTypeUpdate;
use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Domain\ValueObject\UlidInterface;
use InvalidArgumentException;

class CustomerType implements CustomerTypeInterface
{
    private mixed $ulid;

    public function __construct(
        private string $value,
        UlidInterface $ulid
    ) {
        $this->ulid = $ulid;
    }

    public function getUlid(): string
    {
        $this->ulid = $this->normalizeUlid($this->ulid);

        return (string) $this->ulid;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setUlid(UlidInterface $ulid): void
    {
        $this->ulid = $ulid;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function update(CustomerTypeUpdate $updateData): void
    {
        $this->value = $updateData->value;
    }

    private function normalizeUlid(mixed $ulid): UlidInterface
    {
        if ($ulid instanceof UlidInterface) {
            return $ulid;
        }

        if (is_string($ulid)) {
            return new Ulid($ulid);
        }

        if (method_exists($ulid, 'getData')) {
            return Ulid::fromBinary($ulid->getData());
        }

        throw new InvalidArgumentException('Unsupported ulid value for CustomerType.');
    }
}
