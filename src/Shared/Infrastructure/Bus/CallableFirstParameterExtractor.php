<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus;

use ReflectionClass;

final class CallableFirstParameterExtractor
{
    public function extract(object|string $class): ?string
    {
        $reflector = new ReflectionClass($class);
        if (!$reflector->hasMethod('__invoke')) {
            return null;
        }

        $method = $reflector->getMethod('__invoke');
        $parameters = $method->getParameters();
        if (count($parameters) !== 1) {
            return null;
        }

        $type = $parameters[0]->getType();
        if (!$type instanceof \ReflectionNamedType) {
            return null;
        }

        return $type->isBuiltin() ? null : $type->getName();
    }
}
