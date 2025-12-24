<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus;

use LogicException;
use ReflectionClass;

final class CallableFirstParameterExtractor
{
    public function extract(object|string $class): ?string
    {
        $method = $this->invokeMethod($class);

        if ($method->getNumberOfParameters() !== 1) {
            return null;
        }

        $type = $this->firstParameterType($method);

        return $type->getName();
    }

    private function invokeMethod(object|string $class): \ReflectionMethod
    {
        $reflector = new ReflectionClass($class);

        try {
            return $reflector->getMethod('__invoke');
        } catch (\ReflectionException $exception) {
            throw new LogicException(
                sprintf(
                    'Handler "%s" must declare an __invoke method.',
                    $reflector->getName()
                ),
                previous: $exception
            );
        }
    }

    private function firstParameterType(\ReflectionMethod $method): \ReflectionNamedType
    {
        $type = $method->getParameters()[0]->getType();

        if ($type === null) {
            throw new LogicException(
                'Missing type hint for the first parameter of __invoke.'
            );
        }

        if (!$type instanceof \ReflectionNamedType) {
            throw new LogicException(
                'First parameter of __invoke must be a single named (non-union) class type.'
            );
        }

        if ($type->isBuiltin()) {
            throw new LogicException('First parameter of __invoke must be a class type.');
        }

        return $type;
    }
}
