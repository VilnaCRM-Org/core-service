<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus;

use LogicException;
use ReflectionClass;

final class CallableFirstParameterExtractor
{
    /**
     * @param iterable<object> $callables
     *
     * @return array<string, array<object>>
     */
    public static function forCallables(iterable $callables): array
    {
        $extractor = new self();
        $result = [];

        foreach ($callables as $callable) {
            $messageClass = $extractor->extract($callable);
            if ($messageClass !== null) {
                $result[$messageClass][] = $callable;
            }
        }

        return $result;
    }

    public function extract(object|string $class): ?string
    {
        $reflector = new ReflectionClass($class);
        $method = $reflector->getMethod('__invoke');

        if ($this->hasOnlyOneParameter($method)) {
            return $this->firstParameterClassFrom($method);
        }

        return null;
    }

    private function firstParameterClassFrom(\ReflectionMethod $method): string
    {
        /** @var \ReflectionNamedType $firstParameterType */
        $firstParameterType = $method->getParameters()[0]->getType();

        if ($firstParameterType === null) {
            throw new LogicException(
                'Missing type hint for the first parameter of __invoke'
            );
        }

        return $firstParameterType->getName();
    }

    private function hasOnlyOneParameter(\ReflectionMethod $method): bool
    {
        return $method->getNumberOfParameters() === 1;
    }
}
