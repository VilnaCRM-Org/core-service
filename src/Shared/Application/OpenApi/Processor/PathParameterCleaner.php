<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model;

final class PathParameterCleaner
{
    public function clean(mixed $parameter): mixed
    {
        if (!$parameter instanceof Model\Parameter) {
            return $parameter;
        }

        if ($parameter->getIn() !== 'path') {
            return $parameter;
        }

        return new Model\Parameter(
            name: $parameter->getName(),
            in: $parameter->getIn(),
            description: $parameter->getDescription(),
            required: $parameter->getRequired(),
            schema: $parameter->getSchema()
        );
    }
}
