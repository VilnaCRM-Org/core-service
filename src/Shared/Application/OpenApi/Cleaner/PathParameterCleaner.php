<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Cleaner;

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

        // The ParameterNormalizer removes allowEmptyValue and allowReserved during serialization
        // This method just validates it's a path parameter - no additional cleaning needed
        return $parameter;
    }
}
