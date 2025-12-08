<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Cleaner;

/**
 * @internal Allows replacing the cleaner during tests.
 */
interface PathParameterCleanerInterface
{
    public function clean(mixed $parameter): mixed;
}
