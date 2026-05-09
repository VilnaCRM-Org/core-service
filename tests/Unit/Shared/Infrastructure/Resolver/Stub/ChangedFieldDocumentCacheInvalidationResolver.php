<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Resolver\Stub;

use App\Shared\Application\Command\CacheRefreshCommand;
use App\Shared\Application\DTO\CacheChangeSet;
use App\Shared\Application\DTO\CacheInvalidationRule;
use App\Shared\Application\DTO\CacheInvalidationTagSet;
use App\Shared\Application\DTO\CacheRefreshPolicy;
use App\Shared\Application\Resolver\DocumentCacheInvalidationResolverInterface;
use App\Shared\Infrastructure\Collection\CacheRefreshCommandCollection;

final class ChangedFieldDocumentCacheInvalidationResolver implements
    DocumentCacheInvalidationResolverInterface
{
    private ?object $document = null;
    private ?string $operation = null;
    private ?CacheChangeSet $changeSet = null;

    public function supports(object $document, string $operation): bool
    {
        return $document instanceof CacheResolverTestDocument
            && $operation === CacheInvalidationRule::OPERATION_UPDATED;
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
        $this->record($document, $operation, $changeSet);
        $statusChange = $changeSet->get('status');

        \assert($document instanceof CacheResolverTestDocument);

        return CacheInvalidationTagSet::create(
            'document.' . $document->id(),
            'status.' . $statusChange?->oldValue(),
            'status.' . $statusChange?->newValue()
        );
    }

    public function resolveRefreshCommands(
        object $document,
        string $operation,
        CacheChangeSet $changeSet
    ): CacheRefreshCommandCollection {
        $this->record($document, $operation, $changeSet);
        $statusChange = $changeSet->get('status');

        \assert($document instanceof CacheResolverTestDocument);

        return CacheRefreshCommandCollection::create(CacheRefreshCommand::create(
            'document',
            'detail',
            'document_id',
            $document->id(),
            CacheRefreshPolicy::SOURCE_REPOSITORY_REFRESH,
            'odm_change_set',
            (string) $statusChange?->newValue(),
            (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
        ));
    }

    public function document(): ?object
    {
        return $this->document;
    }

    public function operation(): ?string
    {
        return $this->operation;
    }

    public function changeSet(): ?CacheChangeSet
    {
        return $this->changeSet;
    }

    private function record(object $document, string $operation, CacheChangeSet $changeSet): void
    {
        $this->document = $document;
        $this->operation = $operation;
        $this->changeSet = $changeSet;
    }
}
