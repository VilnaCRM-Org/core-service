<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use function array_map;
use ArrayObject;
use function is_array;

final class IriReferenceContentTransformer implements IriReferenceContentTransformerInterface
{
    private readonly IriReferenceMediaTypeTransformerInterface $mediaTypeTransformer;

    public function __construct(
        ?IriReferenceMediaTypeTransformerInterface $mediaTypeTransformer = null
    ) {
        $this->mediaTypeTransformer = $mediaTypeTransformer
            ?? new IriReferenceMediaTypeTransformer();
    }

    /**
     * @return array<string, scalar|array<string, scalar|null>>|null
     */
    public function transform(ArrayObject $content): ?array
    {
        $original = $content->getArrayCopy();
        $transformed = array_map(
            $this->transformDefinition(...),
            $original
        );

        return $transformed === $original ? null : $transformed;
    }

    private function transformDefinition(mixed $definition): mixed
    {
        return is_array($definition)
            ? $this->mediaTypeTransformer->transform($definition)
            : $definition;
    }
}
