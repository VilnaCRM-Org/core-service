<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Resolver;

use App\Shared\Application\CommandHandler\CacheRefreshCommandHandlerBase;
use App\Shared\Application\Resolver\CacheRefreshCommandHandlerResolverInterface;
use RuntimeException;

/**
 * @psalm-suppress UnusedClass Wired through CacheRefreshCommandHandlerResolverInterface.
 */
final readonly class CacheRefreshCommandHandlerResolver implements
    CacheRefreshCommandHandlerResolverInterface
{
    /**
     * @param iterable<CacheRefreshCommandHandlerBase> $handlers
     */
    public function __construct(
        private iterable $handlers
    ) {
    }

    public function resolve(string $context): CacheRefreshCommandHandlerBase
    {
        foreach ($this->handlers as $handler) {
            if ($handler->context() === $context) {
                return $handler;
            }
        }

        throw new RuntimeException(sprintf(
            'No cache refresh handler registered for context "%s".',
            $context
        ));
    }
}
