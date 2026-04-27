<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Resolver;

use App\Shared\Application\Command\CacheInvalidationCommand;
use App\Shared\Application\DTO\CacheChangeSet;
use App\Shared\Application\Resolver\DocumentCacheInvalidationResolverInterface;

final readonly class CacheInvalidationTagResolver
{
    /**
     * @param iterable<DocumentCacheInvalidationResolverInterface> $resolvers
     */
    public function __construct(
        private iterable $resolvers
    ) {
    }

    public function resolveForDocumentChange(
        object $document,
        string $operation,
        CacheChangeSet $changeSet
    ): ?CacheInvalidationCommand {
        foreach ($this->resolvers as $resolver) {
            if (! $resolver->supports($document, $operation)) {
                continue;
            }

            return CacheInvalidationCommand::create(
                $resolver->context($document, $operation),
                'odm_change_set',
                $operation,
                $resolver->resolveTags($document, $operation, $changeSet),
                $resolver->resolveRefreshCommands($document, $operation, $changeSet)
            );
        }

        return null;
    }
}
