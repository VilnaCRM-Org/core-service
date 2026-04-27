<?php

declare(strict_types=1);

namespace App\Shared\Application\DTO;

final readonly class CacheRefreshTarget
{
    private function __construct(
        private string $context,
        private string $family,
        private string $identifierName,
        private string $identifierValue
    ) {
    }

    public static function create(
        string $context,
        string $family,
        string $identifierName,
        string $identifierValue
    ): self {
        return new self($context, $family, $identifierName, $identifierValue);
    }

    public function context(): string
    {
        return $this->context;
    }

    public function family(): string
    {
        return $this->family;
    }

    public function identifierName(): string
    {
        return $this->identifierName;
    }

    public function identifierValue(): string
    {
        return $this->identifierValue;
    }
}
