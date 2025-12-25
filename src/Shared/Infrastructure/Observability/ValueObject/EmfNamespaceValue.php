<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\ValueObject;

/**
 * Represents an AWS CloudWatch EMF namespace
 *
 * AWS CloudWatch namespace constraints (validated via Symfony Validator in YAML config):
 * - 1-256 characters
 * - Only ASCII alphanumeric and these characters: . - _ / # :
 * - Must contain at least one non-whitespace character
 *
 * Validation is performed by EmfNamespaceValidatorService using Symfony's ValidatorInterface.
 */
final readonly class EmfNamespaceValue
{
    public function __construct(
        private string $value
    ) {
    }

    public function value(): string
    {
        return $this->value;
    }
}
