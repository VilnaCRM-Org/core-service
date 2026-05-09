<?php

declare(strict_types=1);

namespace App\Shared\Application\Command;

use App\Shared\Application\DTO\CacheInvalidationTagSet;
use App\Shared\Domain\Bus\Command\CommandInterface;
use App\Shared\Infrastructure\Collection\CacheRefreshCommandCollection;

final readonly class CacheInvalidationCommand implements CommandInterface
{
    private function __construct(
        private string $context,
        private string $source,
        private string $operation,
        private string $dedupeKey,
        private CacheInvalidationTagSet $tags,
        private CacheRefreshCommandCollection $refreshCommands
    ) {
    }

    public static function create(
        string $context,
        string $source,
        string $operation,
        CacheInvalidationTagSet $tags,
        CacheRefreshCommandCollection $refreshCommands
    ): self {
        return new self(
            $context,
            $source,
            $operation,
            self::buildDedupeKey($context, $source, $operation, $tags),
            $tags,
            $refreshCommands
        );
    }

    public function context(): string
    {
        return $this->context;
    }

    public function source(): string
    {
        return $this->source;
    }

    public function operation(): string
    {
        return $this->operation;
    }

    public function dedupeKey(): string
    {
        return $this->dedupeKey;
    }

    public function tags(): CacheInvalidationTagSet
    {
        return $this->tags;
    }

    public function refreshCommands(): CacheRefreshCommandCollection
    {
        return $this->refreshCommands;
    }

    private static function buildDedupeKey(
        string $context,
        string $source,
        string $operation,
        CacheInvalidationTagSet $tags
    ): string {
        /** @var list<string> $orderedTags */
        $orderedTags = iterator_to_array($tags);
        sort($orderedTags, \SORT_STRING);

        return hash('sha256', json_encode([
            'context' => $context,
            'source' => $source,
            'operation' => $operation,
            'tags' => $orderedTags,
        ], \JSON_THROW_ON_ERROR));
    }
}
