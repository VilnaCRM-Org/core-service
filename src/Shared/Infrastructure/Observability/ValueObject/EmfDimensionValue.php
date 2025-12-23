<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\ValueObject;

/**
 * Represents a single dimension key-value pair in EMF format
 */
final readonly class EmfDimensionValue
{
    public function __construct(
        private string $key,
        private string $value
    ) {
    }

    public function key(): string
    {
        return $this->key;
    }

    public function value(): string
    {
        return $this->value;
    }
}
