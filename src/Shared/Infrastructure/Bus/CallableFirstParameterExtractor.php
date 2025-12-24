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

        try {
            $method = $reflector->getMethod('__invoke');
        } catch (\ReflectionException $exception) {
            throw new LogicException(
                sprintf('Handler "%s" must declare an __invoke method.', $reflector->getName()),
                previous: $exception
            );
        }

        if ($this->hasOnlyOneParameter($method)) {
            return $this->firstParameterClassFrom($method);
        }

        return null;
    }

    private function firstParameterClassFrom(\ReflectionMethod $method): string
    {
        $type = $method->getParameters()[0]->getType();

        if (!$type instanceof \ReflectionNamedType) {
            throw new LogicException('First parameter of __invoke must be a single named (non-union) class type.');
        }

        if ($type->isBuiltin()) {
            throw new LogicException('First parameter of __invoke must be a class type, builtin types are not supported.');
        }

        $name = $type->getName();
        if (in_array($name, ['self', 'static', 'parent'], true)) {
            throw new LogicException('First parameter of __invoke must be a concrete class name.');
        }

        return $name;
    }

    private function hasOnlyOneParameter(\ReflectionMethod $method): bool
    {
        return $method->getNumberOfParameters() === 1;
    }
}
