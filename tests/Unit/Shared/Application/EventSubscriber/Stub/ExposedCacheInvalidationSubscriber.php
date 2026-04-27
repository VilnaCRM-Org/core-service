<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\EventSubscriber\Stub;

use App\Shared\Application\DTO\CacheInvalidationTagSet;
use App\Shared\Application\EventSubscriber\AbstractCacheInvalidationSubscriber;
use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Infrastructure\Collection\CacheRefreshCommandCollection;

final readonly class ExposedCacheInvalidationSubscriber extends AbstractCacheInvalidationSubscriber
{
    /**
     * @return array<class-string<DomainEvent>>
     */
    public function subscribedTo(): array
    {
        return [];
    }

    public function callInvalidate(
        string $context,
        string $operation,
        CacheInvalidationTagSet $tags,
        CacheRefreshCommandCollection $refreshCommands
    ): void {
        $this->invalidate($context, $operation, $tags, $refreshCommands);
    }
}
