<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\EventListener;

use App\Shared\Application\Command\CacheInvalidationCommand;
use App\Shared\Application\CommandHandler\CacheInvalidationCommandHandler;
use App\Shared\Application\DTO\CacheInvalidationRule;
use App\Shared\Infrastructure\EventListener\CacheInvalidationDoctrineEventListener;
use App\Shared\Infrastructure\Resolver\CacheInvalidationTagResolver;
use App\Tests\Unit\Shared\Infrastructure\EventListener\Stub as ListenerStub;
use App\Tests\Unit\UnitTestCase;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Event\OnFlushEventArgs;
use Doctrine\ODM\MongoDB\Event\PostFlushEventArgs;
use Doctrine\ODM\MongoDB\UnitOfWork;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

final class CacheInvalidationDoctrineEventListenerTest extends UnitTestCase
{
    private CacheInvalidationCommandHandler&MockObject $handler;
    private LoggerInterface&MockObject $logger;
    private ListenerStub\DoctrineListenerTestDocumentCacheResolver $documentResolver;
    private CacheInvalidationDoctrineEventListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = $this->createMock(CacheInvalidationCommandHandler::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->documentResolver = new ListenerStub\DoctrineListenerTestDocumentCacheResolver();
        $this->listener = new CacheInvalidationDoctrineEventListener(
            new CacheInvalidationTagResolver([$this->documentResolver]),
            $this->handler,
            $this->logger
        );
    }

    public function testOnFlushQueuesInsertUpsertUpdateAndDeleteCommandsUntilPostFlush(): void
    {
        $inserted = new \stdClass();
        $upserted = new \stdClass();
        $updated = new \stdClass();
        $deleted = new \stdClass();
        $manager = $this->documentManager([
            'insertions' => [$inserted],
            'upserts' => [$upserted],
            'updates' => [$updated],
            'deletions' => [$deleted],
            'changeSet' => ['email' => ['old@example.com', 'new@example.com']],
        ]);
        $commands = [];

        $this->handler
            ->expects($this->exactly(4))
            ->method('__invoke')
            ->willReturnCallback(
                static function (CacheInvalidationCommand $command) use (&$commands): void {
                    $commands[] = $command;
                }
            );

        $this->listener->onFlush(new OnFlushEventArgs($manager));
        $this->listener->postFlush(new PostFlushEventArgs($manager));

        $this->assertQueuedOperations($commands);
        self::assertCount(4, $this->documentResolver->changeSets());
        self::assertSame(
            'new@example.com',
            $this->documentResolver->changeSets()[2]->get('email')?->newValue()
        );
    }

    public function testPostFlushLogsAndContinuesWhenInvalidationHandlerFails(): void
    {
        $document = new \stdClass();
        $manager = $this->documentManager([
            'insertions' => [$document],
            'upserts' => [],
            'updates' => [],
            'deletions' => [],
            'changeSet' => [],
        ]);

        $this->handler
            ->expects($this->once())
            ->method('__invoke')
            ->willThrowException(new \RuntimeException('cache backend failed'));

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'Post-flush cache invalidation failed',
                $this->callback(self::assertPostFlushFailureContext(...))
            );

        $this->listener->onFlush(new OnFlushEventArgs($manager));
        $this->listener->postFlush(new PostFlushEventArgs($manager));
    }

    public function testSkipsDocumentsWithoutResolvedInvalidationCommand(): void
    {
        $manager = $this->documentManager([
            'insertions' => [new \stdClass()],
            'upserts' => [],
            'updates' => [],
            'deletions' => [],
            'changeSet' => [],
        ]);
        $handler = $this->createMock(CacheInvalidationCommandHandler::class);
        $listener = new CacheInvalidationDoctrineEventListener(
            new CacheInvalidationTagResolver([
                new ListenerStub\UnsupportedDoctrineListenerTestDocumentCacheResolver(),
            ]),
            $handler,
            $this->logger
        );

        $handler
            ->expects($this->never())
            ->method('__invoke');

        $listener->onFlush(new OnFlushEventArgs($manager));
        $listener->postFlush(new PostFlushEventArgs($manager));
    }

    public function testOnFlushLogsAndContinuesWhenResolverFails(): void
    {
        $manager = $this->documentManager([
            'insertions' => [new \stdClass()],
            'upserts' => [],
            'updates' => [],
            'deletions' => [],
            'changeSet' => [],
        ]);
        $handler = $this->createMock(CacheInvalidationCommandHandler::class);
        $listener = new CacheInvalidationDoctrineEventListener(
            new CacheInvalidationTagResolver([
                new ListenerStub\ThrowingDoctrineListenerTestDocumentCacheResolver(),
            ]),
            $handler,
            $this->logger
        );

        $handler
            ->expects($this->never())
            ->method('__invoke');
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'On-flush cache invalidation command resolution failed',
                $this->callback(self::assertOnFlushResolutionFailureContext(...))
            );

        $listener->onFlush(new OnFlushEventArgs($manager));
        $listener->postFlush(new PostFlushEventArgs($manager));
    }

    /**
     * @param array{
     *     insertions: list<object>,
     *     upserts: list<object>,
     *     updates: list<object>,
     *     deletions: list<object>,
     *     changeSet: array<string, array{0: string|null, 1: string|null}>
     * } $scheduledDocuments
     */
    private function documentManager(array $scheduledDocuments): DocumentManager&MockObject
    {
        $unitOfWork = $this->createMock(UnitOfWork::class);
        $unitOfWork
            ->method('getScheduledDocumentInsertions')
            ->willReturn($scheduledDocuments['insertions']);
        $unitOfWork
            ->method('getScheduledDocumentUpserts')
            ->willReturn($scheduledDocuments['upserts']);
        $unitOfWork
            ->method('getScheduledDocumentUpdates')
            ->willReturn($scheduledDocuments['updates']);
        $unitOfWork
            ->method('getScheduledDocumentDeletions')
            ->willReturn($scheduledDocuments['deletions']);
        $unitOfWork
            ->method('getDocumentChangeSet')
            ->willReturn($scheduledDocuments['changeSet']);

        $manager = $this->createMock(DocumentManager::class);
        $manager
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        return $manager;
    }

    /**
     * @param list<CacheInvalidationCommand> $commands
     */
    private function assertQueuedOperations(array $commands): void
    {
        self::assertSame([
            CacheInvalidationRule::OPERATION_CREATED,
            CacheInvalidationRule::OPERATION_CREATED,
            CacheInvalidationRule::OPERATION_UPDATED,
            CacheInvalidationRule::OPERATION_DELETED,
        ], array_map(
            static fn (CacheInvalidationCommand $command): string => $command->operation(),
            $commands
        ));
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function assertPostFlushFailureContext(array $context): bool
    {
        return $context['operation'] === 'cache.invalidation.error'
            && $context['source'] === 'odm_change_set'
            && $context['error'] === 'cache backend failed'
            && isset($context['dedupe_key'])
            && $context['exception'] instanceof \Throwable;
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function assertOnFlushResolutionFailureContext(array $context): bool
    {
        return $context['operation'] === 'cache.invalidation.error'
            && $context['source'] === 'odm_change_set'
            && $context['cache_operation'] === CacheInvalidationRule::OPERATION_CREATED
            && $context['document_class'] === \stdClass::class
            && $context['error'] === 'resolver failed'
            && $context['exception'] instanceof \Throwable;
    }
}
