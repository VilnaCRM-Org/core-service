<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus;

use App\Shared\Infrastructure\Bus\CallableFirstParameterExtractor;
use App\Shared\Infrastructure\Bus\MessageBusFactory;
use App\Tests\Unit\UnitTestCase;

final class MessageBusFactoryTest extends UnitTestCase
{
    public function testCreateCommandBus(): void
    {
        $commandHandlers = [];
        $extractor = new CallableFirstParameterExtractor();
        $factory = new MessageBusFactory($extractor);

        $commandBus = $factory->createCommandBus($commandHandlers);

        $this->assertInstanceOf(
            \App\Shared\Domain\Bus\Command\CommandBusInterface::class,
            $commandBus
        );
    }

    public function testCreateQueryBus(): void
    {
        $queryHandlers = [];
        $extractor = new CallableFirstParameterExtractor();
        $factory = new MessageBusFactory($extractor);

        $queryBus = $factory->createQueryBus($queryHandlers);

        /**
         * @psalm-suppress UndefinedClass
         */
        $this->assertInstanceOf(
            \App\Shared\Domain\Bus\Query\QueryBusInterface::class,
            $queryBus
        );
    }

    public function testCreateEventBus(): void
    {
        $subscribers = [];
        $extractor = new CallableFirstParameterExtractor();
        $factory = new MessageBusFactory($extractor);

        $eventBus = $factory->createEventBus($subscribers);

        /**
         * @psalm-suppress UndefinedClass
         */
        $this->assertInstanceOf(
            \App\Shared\Domain\Bus\Event\DomainEventBusInterface::class,
            $eventBus
        );
    }
}
