<?php

declare(strict_types=1);

namespace App\Shared\Application\Factory;

use App\Shared\Application\Command\CacheRefreshCommand;
use App\Shared\Application\DTO\CacheRefreshTarget;

/**
 * @psalm-suppress UnusedClass Shared base class for context-specific cache refresh command factories.
 */
abstract readonly class AbstractCacheRefreshCommandFactory
{
    protected function createRefreshCommand(
        CacheRefreshTarget $target,
        string $refreshSource,
        string $sourceName,
        string $sourceId,
        string $occurredOn
    ): CacheRefreshCommand {
        return CacheRefreshCommand::create(
            $target->context(),
            $target->family(),
            $target->identifierName(),
            $target->identifierValue(),
            $refreshSource,
            $sourceName,
            $sourceId,
            $occurredOn
        );
    }
}
