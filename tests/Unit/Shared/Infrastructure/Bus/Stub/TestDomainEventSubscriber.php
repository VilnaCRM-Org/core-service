<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Stub;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use PHPUnit\Framework\Assert;

final class TestDomainEventSubscriber implements DomainEventSubscriberInterface
{
    public function __invoke(DomainEvent $event): void
    {
        Assert::assertNotNull($event);
    }

    /**
     * @return array<class-string>
     */
    public function subscribedTo(): array
    {
        return [DomainEvent::class];
    }
}
