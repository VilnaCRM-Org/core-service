<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus;

use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use LogicException;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

final class CallableFirstParameterExtractor
{
    /**
     * @param array<DomainEventSubscriberInterface> $callables
     *
     * @return array<string, array<DomainEventSubscriberInterface>>
     */
    public function forCallables(array $callables): array
    {
        $indexedCallables = [];

        foreach ($callables as $callable) {
            $indexedCallables[''][] = $callable;
        }

        return $indexedCallables;
    }

    /**
     * @param array<DomainEventSubscriberInterface> $callables
     *
     * @return array<string, array<DomainEventSubscriberInterface>>
     */
    public function forPipedCallables(array $callables): array
    {
        $indexedCallables = [];

        foreach ($callables as $callable) {
            $this->pipedCallablesFor($indexedCallables, $callable);
        }

        return $indexedCallables;
    }

    public function extract(DomainEventSubscriberInterface $subscriber): string
    {
        $subscriberClass = $subscriber::class;
        $method = $this->getInvokeMethod($subscriberClass);
        $this->validateMethodParameters($method, $subscriberClass);

        return $this->extractFirstParameterType($method, $subscriberClass);
    }

    private function getInvokeMethod(string $subscriberClass): ReflectionMethod
    {
        try {
            $reflection = new ReflectionClass($subscriberClass);
            return $reflection->getMethod('__invoke');
        } catch (ReflectionException $error) {
            $message = sprintf(
                'Trying to get a method \'__invoke\' from a class \'%s\' ' .
                'that does not exist. %s',
                $subscriberClass,
                $error->getMessage()
            );
            throw new LogicException($message);
        }
    }

    private function validateMethodParameters(
        ReflectionMethod $method,
        string $subscriberClass
    ): void {
        if ($method->getNumberOfParameters() !== 1) {
            $message = sprintf(
                'Method \'__invoke\' of class \'%s\' has an invalid number ' .
                'of arguments. Expected 1, given %d',
                $subscriberClass,
                $method->getNumberOfParameters()
            );
            throw new LogicException($message);
        }
    }

    private function extractFirstParameterType(
        ReflectionMethod $method,
        string $subscriberClass
    ): string {
        $firstParameter = $method->getParameters()[0];
        $firstParameterType = $firstParameter->getType();

        if (!$firstParameterType instanceof \ReflectionNamedType) {
            $message = sprintf(
                'Method \'__invoke\' of class \'%s\' has an invalid first ' .
                'parameter. Expected a class, given none.',
                $subscriberClass
            );
            throw new LogicException($message);
        }

        return $firstParameterType->getName();
    }

    /**
     * @param array<string, array<DomainEventSubscriberInterface>> $indexedCallables
     */
    private function pipedCallablesFor(
        array &$indexedCallables,
        DomainEventSubscriberInterface $callable
    ): void {
        $subscribedEvents = $callable->subscribedTo();

        foreach ($subscribedEvents as $eventClass) {
            $indexedCallables[$eventClass][] = $callable;
        }
    }
}
