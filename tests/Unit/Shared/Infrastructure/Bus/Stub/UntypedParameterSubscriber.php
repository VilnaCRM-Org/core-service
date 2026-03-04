<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Stub;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use PHPUnit\Framework\Assert;

/**
 * Stub subscriber with untyped __invoke parameter for testing.
 * Used to test behavior with missing type hint in InvokeParameterExtractor.
 *
 * @psalm-suppress MissingParamType
 */
final class UntypedParameterSubscriber implements DomainEventSubscriberInterface
{
    /**
     * No type hint to test extractor returns null for untyped parameters.
     */
    public function __invoke($someClass): void
    {
        Assert::assertNotNull($someClass);
    }

    /**
     * @return array<class-string>
     */
    public function subscribedTo(): array
    {
        return [DomainEvent::class];
    }
}
