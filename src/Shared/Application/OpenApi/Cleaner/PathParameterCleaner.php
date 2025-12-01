<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Cleaner;

use ApiPlatform\OpenApi\Model;

final class PathParameterCleaner implements PathParameterCleanerInterface
{
    public function clean(mixed $parameter): mixed
    {
        if (!$parameter instanceof Model\Parameter) {
            return $parameter;
        }

        if ($parameter->getIn() !== 'path') {
            return $parameter;
        }

        // Ensure OpenAPI path parameters are always marked as required
        if ($parameter->getRequired() === true) {
            return $parameter;
        }

        return $parameter->withRequired(true);
    }
}
