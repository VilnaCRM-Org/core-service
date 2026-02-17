<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Cleaner;

use ApiPlatform\OpenApi\Model;

final class PathParameterCleaner implements PathParameterCleanerInterface
{
    #[\Override]
    public function clean(mixed $parameter): mixed
    {
        return $parameter instanceof Model\Parameter && $parameter->getIn() === 'path'
            ? $this->ensureRequired($parameter)
            : $parameter;
    }

    private function ensureRequired(Model\Parameter $parameter): Model\Parameter
    {
        return $parameter->getRequired() === true
            ? $parameter
            : $parameter->withRequired(true);
    }
}
