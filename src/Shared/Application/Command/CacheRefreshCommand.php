<?php

declare(strict_types=1);

namespace App\Shared\Application\Command;

final readonly class CacheRefreshCommand
{
    private function __construct(
        private string $context,
        private string $family,
        private string $identifierName,
        private string $identifierValue,
        private string $refreshSource,
        private string $sourceName,
        private string $sourceId,
        private string $occurredOn,
        private string $dedupeKey
    ) {
    }

    public static function create(
        string $context,
        string $family,
        string $identifierName,
        string $identifierValue,
        string $refreshSource,
        string $sourceName,
        string $sourceId,
        string $occurredOn,
        bool $caseInsensitiveIdentifier = true
    ): self {
        return new self(
            $context,
            $family,
            $identifierName,
            $identifierValue,
            $refreshSource,
            $sourceName,
            $sourceId,
            $occurredOn,
            self::buildDedupeKey(
                $context,
                $family,
                $identifierName,
                $identifierValue,
                $refreshSource,
                $sourceName,
                $sourceId,
                $caseInsensitiveIdentifier
            )
        );
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

    public function refreshSource(): string
    {
        return $this->refreshSource;
    }

    public function sourceName(): string
    {
        return $this->sourceName;
    }

    public function sourceId(): string
    {
        return $this->sourceId;
    }

    public function occurredOn(): string
    {
        return $this->occurredOn;
    }

    public function dedupeKey(): string
    {
        return $this->dedupeKey;
    }

    private static function buildDedupeKey(
        string $context,
        string $family,
        string $identifierName,
        string $identifierValue,
        string $refreshSource,
        string $sourceName,
        string $sourceId,
        bool $caseInsensitiveIdentifier
    ): string {
        unset($sourceName, $sourceId);

        return hash('sha256', json_encode([
            'context' => $context,
            'family' => $family,
            'identifier_name' => $identifierName,
            'identifier_value' => self::normalizeIdentifier(
                $identifierValue,
                $caseInsensitiveIdentifier
            ),
            'refresh_source' => $refreshSource,
        ], \JSON_THROW_ON_ERROR));
    }

    private static function normalizeIdentifier(
        string $identifierValue,
        bool $caseInsensitiveIdentifier
    ): string {
        if ($caseInsensitiveIdentifier) {
            return strtolower($identifierValue);
        }

        return $identifierValue;
    }
}
