<?php

declare(strict_types=1);

namespace App\Shared\Application\DTO;

final readonly class CacheRefreshResult
{
    private function __construct(
        private string $context,
        private string $family,
        private string $dedupeKey,
        private bool $wasRefreshed,
        private string $reason
    ) {
    }

    public static function success(string $context, string $family, string $dedupeKey): self
    {
        return new self($context, $family, $dedupeKey, true, 'refreshed');
    }

    public static function skipped(
        string $context,
        string $family,
        string $dedupeKey,
        string $reason
    ): self {
        return new self($context, $family, $dedupeKey, false, $reason);
    }

    public function context(): string
    {
        return $this->context;
    }

    public function family(): string
    {
        return $this->family;
    }

    public function dedupeKey(): string
    {
        return $this->dedupeKey;
    }

    public function refreshed(): bool
    {
        return $this->wasRefreshed;
    }

    public function reason(): string
    {
        return $this->reason;
    }
}
