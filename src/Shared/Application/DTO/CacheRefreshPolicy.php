<?php

declare(strict_types=1);

namespace App\Shared\Application\DTO;

final readonly class CacheRefreshPolicy
{
    public const SOURCE_REPOSITORY_REFRESH = 'repository_refresh';
    public const SOURCE_EVENT_SNAPSHOT = 'event_snapshot';
    public const SOURCE_INVALIDATE_ONLY = 'invalidate_only';

    private function __construct(
        private string $context,
        private string $family,
        private int $ttlSeconds,
        private ?float $beta,
        private string $consistency,
        private string $refreshSource
    ) {
    }

    public static function create(
        string $context,
        string $family,
        int $ttlSeconds,
        ?float $beta,
        string $consistency,
        string $refreshSource
    ): self {
        return new self($context, $family, $ttlSeconds, $beta, $consistency, $refreshSource);
    }

    public function context(): string
    {
        return $this->context;
    }

    public function family(): string
    {
        return $this->family;
    }

    public function ttlSeconds(): int
    {
        return $this->ttlSeconds;
    }

    public function beta(): ?float
    {
        return $this->beta;
    }

    public function consistency(): string
    {
        return $this->consistency;
    }

    public function refreshSource(): string
    {
        return $this->refreshSource;
    }
}
