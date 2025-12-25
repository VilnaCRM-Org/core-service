<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus;

use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use Symfony\Component\Messenger\Handler\HandlerDescriptor;
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
     * @return array<string, array<HandlerDescriptor>>
     */
    private function buildHandlersMap(iterable $handlers): array
    {
        $extractor = new CallableFirstParameterExtractor();

        return array_reduce(
            iterator_to_array($handlers),
            fn (array $map, object $handler): array => $this->mapHandler(
                $map,
                $handler,
                $extractor
            ),
            []
        );
    }

    /**
     * @param array<string, array<HandlerDescriptor>> $map
     *
     * @return array<string, array<HandlerDescriptor>>
     */
    private function mapHandler(
        array $map,
        object $handler,
        CallableFirstParameterExtractor $extractor
    ): array {
        $descriptor = new HandlerDescriptor($handler, [
            'alias' => sprintf('%d', spl_object_id($handler)),
        ]);

        if ($handler instanceof DomainEventSubscriberInterface) {
            foreach ($handler->subscribedTo() as $messageClass) {
                $map[$messageClass][] = $descriptor;
            }

            return $map;
        }

        $messageClass = $extractor->extract($handler);
        if ($messageClass !== null) {
            $map[$messageClass][] = $descriptor;
        }

        return $map;
    }
}
