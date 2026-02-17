<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Stub;

use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use PHPUnit\Framework\Assert;

final class TestOtherEventSubscriber implements DomainEventSubscriberInterface
{
    public function __invoke(TestOtherEvent $event): void
    {
        Assert::assertNotNull($event);
    }

    /**
     * @return array<class-string>
     */
    #[\Override]
    public function subscribedTo(): array
    {
        return [TestOtherEvent::class];
    }
}
