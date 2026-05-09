<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventListener;

use App\Shared\Application\Command\CacheInvalidationCommand;
use App\Shared\Application\CommandHandler\CacheInvalidationCommandHandler;
use App\Shared\Application\DTO\CacheChangeSet;
use App\Shared\Application\DTO\CacheInvalidationRule;
use App\Shared\Infrastructure\Resolver\CacheInvalidationTagResolver;
use ArrayIterator;
use Doctrine\ODM\MongoDB\Event\OnFlushEventArgs;
use Doctrine\ODM\MongoDB\Event\PostFlushEventArgs;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * @psalm-suppress UnusedClass Wired through Doctrine ODM event listener tags.
 */
final class CacheInvalidationDoctrineEventListener
{
    /** @var ArrayIterator<int, CacheInvalidationCommand> */
    private ArrayIterator $pendingCommands;

    public function __construct(
        private readonly CacheInvalidationTagResolver $resolver,
        private readonly CacheInvalidationCommandHandler $handler,
        private readonly LoggerInterface $logger
    ) {
        $this->pendingCommands = new ArrayIterator();
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $this->pendingCommands = new ArrayIterator();

        $unitOfWork = $args->getDocumentManager()->getUnitOfWork();

        $this->queueCreatedDocuments($unitOfWork);
        $this->queueUpdatedDocuments($unitOfWork);
        $this->queueDeletedDocuments($unitOfWork);
    }

    /**
     * @psalm-suppress UnusedParam Signature required by Doctrine ODM.
     */
    public function postFlush(PostFlushEventArgs $args): void
    {
        $commands = $this->pendingCommands;
        $this->pendingCommands = new ArrayIterator();

        foreach ($commands as $command) {
            $this->handlePendingCommand($command);
        }
    }

    /**
     * @psalm-suppress MixedArgument Doctrine ODM returns managed documents as objects.
     */
    private function queueCreatedDocuments(UnitOfWork $unitOfWork): void
    {
        $this->queueCreatedDocumentBatch(
            $unitOfWork->getScheduledDocumentInsertions()
        );
        $this->queueCreatedDocumentBatch(
            $unitOfWork->getScheduledDocumentUpserts()
        );
    }

    /**
     * @psalm-suppress MixedArgument Doctrine ODM returns managed documents as objects.
     */
    private function queueUpdatedDocuments(UnitOfWork $unitOfWork): void
    {
        foreach ($unitOfWork->getScheduledDocumentUpdates() as $document) {
            $this->queueDocumentChange(
                $document,
                CacheInvalidationRule::OPERATION_UPDATED,
                CacheChangeSet::fromDoctrineChangeSet(
                    $unitOfWork->getDocumentChangeSet($document)
                )
            );
        }
    }

    /**
     * @psalm-suppress MixedArgument Doctrine ODM returns managed documents as objects.
     */
    private function queueDeletedDocuments(UnitOfWork $unitOfWork): void
    {
        foreach ($unitOfWork->getScheduledDocumentDeletions() as $document) {
            $this->queueDocumentChange(
                $document,
                CacheInvalidationRule::OPERATION_DELETED,
                CacheChangeSet::empty()
            );
        }
    }

    private function queueDocumentChange(
        object $document,
        string $operation,
        CacheChangeSet $changeSet
    ): void {
        try {
            $command = $this->resolver->resolveForDocumentChange(
                $document,
                $operation,
                $changeSet
            );
        } catch (Throwable $e) {
            $this->logQueueFailure($document, $operation, $e);

            return;
        }

        if (! $command instanceof CacheInvalidationCommand) {
            return;
        }

        $this->pendingCommands->append($command);
    }

    private function logQueueFailure(object $document, string $operation, Throwable $e): void
    {
        $this->logger->warning('On-flush cache invalidation command resolution failed', [
            'operation' => 'cache.invalidation.error',
            'source' => 'odm_change_set',
            'cache_operation' => $operation,
            'document_class' => $document::class,
            'error' => $e->getMessage(),
            'exception' => $e,
        ]);
    }

    /**
     * @param iterable<object> $documents
     */
    private function queueCreatedDocumentBatch(iterable $documents): void
    {
        foreach ($documents as $document) {
            $this->queueDocumentChange(
                $document,
                CacheInvalidationRule::OPERATION_CREATED,
                CacheChangeSet::empty()
            );
        }
    }

    private function handlePendingCommand(CacheInvalidationCommand $command): void
    {
        try {
            $this->handler->__invoke($command);
        } catch (Throwable $e) {
            $this->logger->warning('Post-flush cache invalidation failed', [
                'operation' => 'cache.invalidation.error',
                'source' => 'odm_change_set',
                'dedupe_key' => $command->dedupeKey(),
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);
        }
    }
}
