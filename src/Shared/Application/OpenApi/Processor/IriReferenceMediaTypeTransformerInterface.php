<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

/**
 * @internal Anchor interface to allow media type transformer doubles.
 */
interface IriReferenceMediaTypeTransformerInterface
{
    /**
     * @param array<string, scalar|array<string, scalar|null>> $mediaType
     *
     * @return array<string, scalar|array<string, scalar|null>>
     */
    public function transform(array $mediaType): array;
}
