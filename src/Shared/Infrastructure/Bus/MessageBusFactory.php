<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;

final class MessageBusFactory
{
    public function __construct(
        private CallableFirstParameterExtractor $parameterExtractor
    ) {
    }

    /**
     * @param array<object> $commandHandlers
     */
    public function createCommandBus(
        array $commandHandlers
    ): CommandBusInterface {
        /**
         * @psalm-suppress UndefinedClass
         */
        return new SymfonyCommandBus(
            new MessageBus([
                new HandleMessageMiddleware(
                    new HandlersLocator(
                        $this->parameterExtractor->forCallables(
                            $commandHandlers
                        )
                    )
                ),
            ])
        );
    }

    /**
     * @param array<object> $queryHandlers
     *
     * @psalm-suppress UndefinedClass
     */
    public function createQueryBus(array $queryHandlers): QueryBusInterface
    {
        /**
         * @psalm-suppress UndefinedClass
         */
        return new SymfonyQueryBus(
            new MessageBus([
                new HandleMessageMiddleware(
                    new HandlersLocator(
                        $this->parameterExtractor->forCallables(
                            $queryHandlers
                        )
                    )
                ),
            ])
        );
    }

    /**
     * @param array<object> $subscribers
     *
     * @psalm-suppress UndefinedClass
     */
    public function createEventBus(array $subscribers): DomainEventBusInterface
    {
        /**
         * @psalm-suppress UndefinedClass
         */
        return new SymfonyDomainEventBus(
            new MessageBus([
                new HandleMessageMiddleware(
                    new HandlersLocator(
                        $this->parameterExtractor->forPipedCallables(
                            $subscribers
                        )
                    )
                ),
            ])
        );
    }
}
