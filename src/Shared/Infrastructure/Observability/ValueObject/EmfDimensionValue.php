<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\ValueObject;

use App\Shared\Infrastructure\Observability\Validator\EmfDimensionValueValidator;

/**
 * Represents a single dimension key-value pair in EMF format
 *
 * Validates against AWS CloudWatch EMF constraints using Symfony Validator:
 * - Keys: 1-255 chars, ASCII only, at least one non-whitespace, cannot start with ':'
 * - Values: 1-1024 chars, ASCII only, at least one non-whitespace
 * - No ASCII control characters allowed in either
 */
final readonly class EmfDimensionValue
{
    public function __construct(
        private string $key,
        private string $value
    ) {
        EmfDimensionValueValidator::validateKey($key);
        EmfDimensionValueValidator::validateValue($value);
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
