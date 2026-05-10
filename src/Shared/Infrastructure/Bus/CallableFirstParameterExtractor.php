<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus;

use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;

final class CallableFirstParameterExtractor
{
    /**
     * @param iterable<DomainEventSubscriberInterface> $callables
     *
     * @return array<int, string|null>
     */
    public function forCallables(iterable $callables): array
    {
        $callableArray = iterator_to_array($callables);
        $extractor = new InvokeParameterExtractor();

        $keys = array_map(
            static fn (object $handler): ?string => $extractor->extract($handler),
            $callableArray
        );

        $values = array_map(
            static fn ($value) => [$value],
            $callableArray
        );

        return array_combine($keys, $values);
    }

    /**
     * @param iterable<DomainEventSubscriberInterface> $callables
     *
     * @return array<int, array<DomainEventSubscriberInterface>>
     */
    public function forPipedCallables(iterable $callables): array
    {
        return array_reduce(
            iterator_to_array($callables),
            $this->pipedCallablesReducer(),
            []
        );
    }

    private function pipedCallablesReducer(): callable
    {
        return fn (
            array $subscribers,
            DomainEventSubscriberInterface $subscriber
        ): array => array_reduce(
            $subscriber->subscribedTo(),
            fn (
                array $carry,
                string $event
            ) => $this->addSubscriberToEvent($carry, $event, $subscriber),
            $subscribers
        );
    }

    /**
     * @param array<DomainEventSubscriberInterface> $subscribers
     *
     * @return array<int, array<DomainEventSubscriberInterface>>
     */
    private function addSubscriberToEvent(
        array $subscribers,
        string $event,
        DomainEventSubscriberInterface $subscriber
    ): array {
        $subscribers[$event][] = $subscriber;

        return $subscribers;
    }
}
