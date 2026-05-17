<?php

declare(strict_types=1);

namespace App\Shared\Application\Factory;

use App\Shared\Application\Command\CacheInvalidationCommand;
use App\Shared\Application\DTO\CacheInvalidationTagSet;
use App\Shared\Infrastructure\Collection\CacheRefreshCommandCollection;

final readonly class CacheInvalidationCommandFactory
{
    public function create(
        string $context,
        string $source,
        string $operation,
        CacheInvalidationTagSet $tags,
        CacheRefreshCommandCollection $refreshCommands
    ): CacheInvalidationCommand {
        return new CacheInvalidationCommand(
            $context,
            $source,
            $operation,
            $tags,
            $refreshCommands
        );
    }
}
