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
