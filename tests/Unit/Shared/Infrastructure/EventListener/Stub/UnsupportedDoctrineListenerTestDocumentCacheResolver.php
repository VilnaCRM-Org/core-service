<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\EventListener\Stub;

use App\Shared\Application\DTO\CacheChangeSet;
use App\Shared\Application\DTO\CacheInvalidationTagSet;
use App\Shared\Application\Resolver\DocumentCacheInvalidationResolverInterface;
use App\Shared\Infrastructure\Collection\CacheRefreshCommandCollection;

final class UnsupportedDoctrineListenerTestDocumentCacheResolver implements
    DocumentCacheInvalidationResolverInterface
{
    public function supports(object $document, string $operation): bool
    {
        return false;
    }

    public function context(object $document, string $operation): string
    {
        throw new \LogicException('Unsupported resolver must not resolve context.');
    }

    public function resolveTags(
        object $document,
        string $operation,
        CacheChangeSet $changeSet
    ): CacheInvalidationTagSet {
        throw new \LogicException('Unsupported resolver must not resolve tags.');
    }

    public function resolveRefreshCommands(
        object $document,
        string $operation,
        CacheChangeSet $changeSet
    ): CacheRefreshCommandCollection {
        throw new \LogicException('Unsupported resolver must not resolve commands.');
    }
}
