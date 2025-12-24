<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus;

/**
 * Builds the handler map expected by Symfony Messenger's HandlersLocator.
 */
final class HandlersLocatorMapBuilder
{
    /**
     * @param iterable<object> $handlers
     *
     * @return array<string, array<object>>
     */
    public static function fromHandlers(iterable $handlers): array
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
