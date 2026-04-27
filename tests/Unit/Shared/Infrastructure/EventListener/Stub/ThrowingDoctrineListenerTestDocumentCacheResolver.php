<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\EventListener\Stub;

use App\Shared\Application\DTO\CacheChangeSet;
use App\Shared\Application\DTO\CacheInvalidationTagSet;
use App\Shared\Application\Resolver\DocumentCacheInvalidationResolverInterface;
use App\Shared\Infrastructure\Collection\CacheRefreshCommandCollection;

final class ThrowingDoctrineListenerTestDocumentCacheResolver implements
    DocumentCacheInvalidationResolverInterface
{
    public function supports(object $document, string $operation): bool
    {
        return true;
    }

    public function context(object $document, string $operation): string
    {
        return 'document';
    }

    public function resolveTags(
        object $document,
        string $operation,
        CacheChangeSet $changeSet
    ): CacheInvalidationTagSet {
        throw new \RuntimeException('resolver failed');
    }

    public function resolveRefreshCommands(
        object $document,
        string $operation,
        CacheChangeSet $changeSet
    ): CacheRefreshCommandCollection {
        throw new \LogicException('Resolver failed before commands are resolved.');
    }
}
