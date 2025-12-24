<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus;

use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;

final class MessageBusFactory
{
    /**
     * @param iterable<MiddlewareInterface> $middlewares
     */
    public function __construct(private iterable $middlewares = [])
    {
    }

    /**
     * @param iterable<object> $handlers
     */
    public function create(iterable $handlers): MessageBus
    {
        $middlewareStack = [...$this->middlewares];
        $middlewareStack[] = new HandleMessageMiddleware(
            new HandlersLocator($this->buildHandlersMap($handlers))
        );

        return new MessageBus($middlewareStack);
    }

    /**
     * @param iterable<object> $handlers
     *
     * @return array<string, array<object>>
     */
    private function buildHandlersMap(iterable $handlers): array
    {
        $extractor = new CallableFirstParameterExtractor();

        return array_reduce(
            iterator_to_array($handlers),
            static function (array $map, object $handler) use ($extractor): array {
                $messageClass = $extractor->extract($handler);
                if ($messageClass !== null) {
                    $map[$messageClass][] = $handler;
                }

                return $map;
            },
            []
        );
    }
}
