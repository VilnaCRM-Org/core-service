<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ArrayObject;

use function is_array;

final class IriReferenceContentTransformer
{
    private IriReferenceMediaTypeTransformer $mediaTypeTransformer;

    public function __construct(?IriReferenceMediaTypeTransformer $mediaTypeTransformer = null)
    {
        $this->mediaTypeTransformer = $mediaTypeTransformer ?? new IriReferenceMediaTypeTransformer();
    }

    /**
     * @return array<string, scalar|array<string, scalar|null>>|null
     */
    public function transform(ArrayObject $content): ?array
    {
        $result = $content->getArrayCopy();
        $wasModified = false;

        foreach ($result as $mediaType => $definition) {
            if (!is_array($definition)) {
                continue;
            }

            $transformed = $this->mediaTypeTransformer->transform($definition);

            if ($transformed === $definition) {
                continue;
            }

            $wasModified = true;
            $result[$mediaType] = $transformed;
        }

        return $wasModified ? $result : null;
    }
}
