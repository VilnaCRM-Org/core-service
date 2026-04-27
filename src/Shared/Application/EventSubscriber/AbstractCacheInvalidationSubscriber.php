<?php

declare(strict_types=1);

namespace App\Shared\Application\EventSubscriber;

use App\Shared\Application\Command\CacheInvalidationCommand;
use App\Shared\Application\CommandHandler\CacheInvalidationCommandHandler;
use App\Shared\Application\DTO\CacheInvalidationTagSet;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\Shared\Infrastructure\Collection\CacheRefreshCommandCollection;
use Psr\Log\LoggerInterface;

/**
 * @psalm-suppress UnusedClass Shared base class for domain cache invalidation subscribers.
 */
abstract readonly class AbstractCacheInvalidationSubscriber implements
    DomainEventSubscriberInterface
{
    public function __construct(
        private CacheInvalidationCommandHandler $handler,
        private LoggerInterface $logger
    ) {
    }

    protected function invalidate(
        string $context,
        string $operation,
        CacheInvalidationTagSet $tags,
        CacheRefreshCommandCollection $refreshCommands
    ): void {
        try {
            $this->handler->__invoke(CacheInvalidationCommand::create(
                $context,
                'domain_event',
                $operation,
                $tags,
                $refreshCommands
            ));
        } catch (\Throwable $e) {
            $this->logger->warning('Domain-event cache invalidation failed', [
                'operation' => 'cache.invalidation.error',
                'cache_operation' => $operation,
                'context' => $context,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);
        }
    }
}
