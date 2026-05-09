<?php

declare(strict_types=1);

namespace App\Shared\Application\Resolver;

use App\Shared\Application\DTO\CacheChangeSet;
use App\Shared\Application\DTO\CacheInvalidationTagSet;
use App\Shared\Infrastructure\Collection\CacheRefreshCommandCollection;

interface DocumentCacheInvalidationResolverInterface
{
    public function supports(object $document, string $operation): bool;

    public function context(object $document, string $operation): string;

    public function resolveTags(
        object $document,
        string $operation,
        CacheChangeSet $changeSet
    ): CacheInvalidationTagSet;

    public function resolveRefreshCommands(
        object $document,
        string $operation,
        CacheChangeSet $changeSet
    ): CacheRefreshCommandCollection;
}
