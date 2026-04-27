<?php

declare(strict_types=1);

namespace App\Shared\Application\DTO;

final readonly class CacheFieldChange
{
    public function __construct(
        private string $field,
        private mixed $oldValue,
        private mixed $newValue
    ) {
    }

    public static function create(string $field, mixed $oldValue, mixed $newValue): self
    {
        return new self($field, $oldValue, $newValue);
    }

    public function field(): string
    {
        return $this->field;
    }

    public function oldValue(): mixed
    {
        return $this->oldValue;
    }

    public function newValue(): mixed
    {
        return $this->newValue;
    }
}
