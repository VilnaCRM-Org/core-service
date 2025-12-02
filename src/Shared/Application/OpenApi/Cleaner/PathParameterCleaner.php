<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Cleaner;

use ApiPlatform\OpenApi\Model;

final class PathParameterCleaner implements PathParameterCleanerInterface
{
    public function clean(mixed $parameter): mixed
    {
        if (!$parameter instanceof Model\Parameter || $parameter->getIn() !== 'path') {
            return $parameter;
        }

        // Ensure OpenAPI path parameters are always marked as required
        return $parameter->getRequired() === true
            ? $parameter
            : $parameter->withRequired(true);
    }
}
