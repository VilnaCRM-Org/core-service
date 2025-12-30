<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Stub;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use PHPUnit\Framework\Assert;

/**
 * Stub subscriber with intentionally untyped __invoke parameter.
 * Used to test exception for missing type hint in InvokeParameterExtractor.
 *
 * @psalm-suppress MissingParamType
 */
final class UntypedParameterSubscriber implements DomainEventSubscriberInterface
{
    /**
     * Intentionally untyped to test exception for missing type hint.
     *
     * @param object $someClass
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
