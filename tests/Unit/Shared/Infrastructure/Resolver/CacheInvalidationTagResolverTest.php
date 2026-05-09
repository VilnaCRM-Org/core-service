<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Resolver;

use App\Shared\Application\Command\CacheRefreshCommand;
use App\Shared\Application\DTO\CacheChangeSet;
use App\Shared\Application\DTO\CacheFieldChange;
use App\Shared\Application\DTO\CacheInvalidationRule;
use App\Shared\Infrastructure\Resolver\CacheInvalidationTagResolver;
use App\Tests\Unit\Shared\Infrastructure\Resolver\Stub as ResolverStub;
use App\Tests\Unit\UnitTestCase;

final class CacheInvalidationTagResolverTest extends UnitTestCase
{
    public function testReturnsNullForUnsupportedDocument(): void
    {
        $resolver = new CacheInvalidationTagResolver([
            new ResolverStub\UnsupportedDocumentCacheInvalidationResolver(),
        ]);

        self::assertNull($resolver->resolveForDocumentChange(
            new ResolverStub\CacheResolverTestDocument((string) $this->faker->ulid()),
            CacheInvalidationRule::OPERATION_UPDATED,
            CacheChangeSet::empty()
        ));
    }

    public function testDelegatesToSupportingResolverWithChangedFields(): void
    {
        $document = new ResolverStub\CacheResolverTestDocument(
            (string) $this->faker->ulid()
        );
        $oldStatus = 'old-status';
        $newStatus = 'new-status';
        $operation = CacheInvalidationRule::OPERATION_UPDATED;
        $changeSet = CacheChangeSet::create(
            CacheFieldChange::create('status', $oldStatus, $newStatus)
        );
        $supportingResolver = new ResolverStub\ChangedFieldDocumentCacheInvalidationResolver();
        $resolver = new CacheInvalidationTagResolver([
            new ResolverStub\UnsupportedDocumentCacheInvalidationResolver(),
            $supportingResolver,
        ]);

        $command = $resolver->resolveForDocumentChange($document, $operation, $changeSet);

        self::assertNotNull($command);
        self::assertSame('document', $command->context());
        self::assertSame('odm_change_set', $command->source());
        self::assertSame($operation, $command->operation());
        $this->assertTags($command->tags(), $document->id(), $oldStatus, $newStatus);

        $refreshCommands = iterator_to_array($command->refreshCommands());
        $this->assertRefreshCommand($refreshCommands, $document->id(), $newStatus);
        self::assertSame($document, $supportingResolver->document());
        self::assertSame($operation, $supportingResolver->operation());
        self::assertSame($changeSet, $supportingResolver->changeSet());
    }

    private function assertTags(
        iterable $tags,
        string $documentId,
        string $oldStatus,
        string $newStatus
    ): void {
        self::assertSame([
            'document.' . $documentId,
            'status.' . $oldStatus,
            'status.' . $newStatus,
        ], iterator_to_array($tags));
    }

    /**
     * @param list<CacheRefreshCommand> $refreshCommands
     */
    private function assertRefreshCommand(
        array $refreshCommands,
        string $documentId,
        string $newStatus
    ): void {
        self::assertCount(1, $refreshCommands);
        self::assertSame($documentId, $refreshCommands[0]->identifierValue());
        self::assertSame($newStatus, $refreshCommands[0]->sourceId());
    }
}
