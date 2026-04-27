<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\EventListener\Stub;

use App\Shared\Application\DTO\CacheChangeSet;
use App\Shared\Application\DTO\CacheInvalidationTagSet;
use App\Shared\Application\Resolver\DocumentCacheInvalidationResolverInterface;
use App\Shared\Infrastructure\Collection\CacheRefreshCommandCollection;

final class DoctrineListenerTestDocumentCacheResolver implements
    DocumentCacheInvalidationResolverInterface
{
    /** @var list<CacheChangeSet> */
    private array $changeSets = [];

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
        $this->changeSets[] = $changeSet;

        return CacheInvalidationTagSet::create(
            'document.' . spl_object_id($document),
            'operation.' . $operation
        );
    }

    public function resolveRefreshCommands(
        object $document,
        string $operation,
        CacheChangeSet $changeSet
    ): CacheRefreshCommandCollection {
        return CacheRefreshCommandCollection::create();
    }

    /**
     * @return list<CacheChangeSet>
     */
    public function changeSets(): array
    {
        return $this->changeSets;
    }
}
