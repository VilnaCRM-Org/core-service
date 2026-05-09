<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Factory;

use App\Core\Customer\Domain\Event\CustomerCreatedEvent;
use App\Core\Customer\Domain\Event\CustomerDeletedEvent;
use App\Core\Customer\Domain\Event\CustomerUpdatedEvent;
use App\Core\Customer\Infrastructure\Collection\CustomerCachePolicyCollection;
use App\Core\Customer\Infrastructure\Resolver\CustomerCacheRefreshTargetResolver;
use App\Shared\Application\Command\CacheRefreshCommand;
use App\Shared\Application\Factory\AbstractCacheRefreshCommandFactory;
use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Infrastructure\Collection\CacheRefreshCommandCollection;
use InvalidArgumentException;

/**
 * @phpstan-type CustomerCacheRefreshTarget array{
 *     context: string,
 *     family: string,
 *     identifiers: non-empty-array<string, string>
 * }
 */
final readonly class CustomerCacheRefreshCommandFactory extends AbstractCacheRefreshCommandFactory
{
    public function __construct(
        private CustomerCacheRefreshTargetResolver $targetResolver
    ) {
    }

    public function createForCreatedEvent(
        CustomerCreatedEvent $event
    ): CacheRefreshCommandCollection {
        return $this->createForTargets(
            $this->targetResolver->resolveForCreatedEvent($event),
            CustomerCachePolicyCollection::REFRESH_SOURCE_REPOSITORY,
            $event
        );
    }

    public function createForUpdatedEvent(
        CustomerUpdatedEvent $event
    ): CacheRefreshCommandCollection {
        return $this->createForTargets(
            $this->targetResolver->resolveForUpdatedEvent($event),
            CustomerCachePolicyCollection::REFRESH_SOURCE_REPOSITORY,
            $event
        );
    }

    public function createForDeletedEvent(
        CustomerDeletedEvent $event
    ): CacheRefreshCommandCollection {
        return $this->createForTargets(
            $this->targetResolver->resolveForDeletedEvent($event),
            CustomerCachePolicyCollection::REFRESH_SOURCE_INVALIDATE_ONLY,
            $event
        );
    }

    /**
     * @param list<CustomerCacheRefreshTarget> $targets
     */
    private function createForTargets(
        array $targets,
        string $refreshSource,
        DomainEvent $event
    ): CacheRefreshCommandCollection {
        $commands = array_map(
            fn (array $target): CacheRefreshCommand => $this->createCommand(
                $target,
                $refreshSource,
                $event
            ),
            $targets
        );

        return CacheRefreshCommandCollection::create(...$commands);
    }

    /**
     * @param CustomerCacheRefreshTarget $target
     */
    private function createCommand(
        array $target,
        string $refreshSource,
        DomainEvent $event
    ): CacheRefreshCommand {
        $identifierName = $this->identifierName($target);
        $identifierValue = $target['identifiers'][$identifierName];

        return $this->createRefreshCommand(
            $this->targetResolver->resolve(
                $target['context'],
                $target['family'],
                (string) $identifierName,
                $identifierValue
            ),
            $refreshSource,
            $event::eventName(),
            $event->eventId(),
            $event->occurredOn()
        );
    }

    /**
     * @param CustomerCacheRefreshTarget $target
     */
    private function identifierName(array $target): string
    {
        $identifierName = array_key_first($target['identifiers']);
        if ($identifierName !== null) {
            return $identifierName;
        }

        throw new InvalidArgumentException(sprintf(
            '%s Target "%s.%s".',
            $this->missingIdentifiersMessage(),
            $target['context'],
            $target['family']
        ));
    }

    private function missingIdentifiersMessage(): string
    {
        return <<<'MESSAGE'
Missing identifiers in createCommand; targetResolver/createRefreshCommand need metadata.
MESSAGE;
    }
}
