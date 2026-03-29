<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Stub;

use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use PHPUnit\Framework\Assert;

/**
 * @psalm-suppress UnusedClass
 */
final class TestOtherEventSubscriber implements DomainEventSubscriberInterface
{
    public function __invoke(TestOtherEvent $event): void
    {
        Assert::assertNotNull($event);
    }

    /**
     * @return array<class-string>
     */
    public function subscribedTo(): array
    {
        return [TestOtherEvent::class];
    }
}
