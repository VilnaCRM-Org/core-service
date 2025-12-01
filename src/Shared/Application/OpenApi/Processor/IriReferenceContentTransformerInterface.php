<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ArrayObject;

/**
 * @internal Anchor interface to allow transformer test doubles.
 */
interface IriReferenceContentTransformerInterface
{
    /**
     * @return array<string, scalar|array<string, scalar|null>>|null
     */
    public function transform(ArrayObject $content): ?array;
}
