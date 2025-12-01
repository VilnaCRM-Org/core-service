<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;

final class IriReferenceTypeProcessor
{
    private const OPERATIONS = ['Get', 'Post', 'Put', 'Patch', 'Delete'];

    public function process(OpenApi $openApi): OpenApi
    {
        $paths = $openApi->getPaths();

        foreach (array_keys($paths->getPaths()) as $path) {
            $pathItem = $paths->getPath($path);

            $processedPathItem = array_reduce(
                self::OPERATIONS,
                fn (PathItem $item, string $operation): PathItem => $this->processOperation($item, $operation),
                $pathItem
            );

            $paths->addPath($path, $processedPathItem);
        }

        return $openApi;
    }

    private function processOperation(PathItem $pathItem, string $operation): PathItem
    {
        $currentOperation = $pathItem->{'get' . $operation}();
        $content = $currentOperation?->getRequestBody()?->getContent();

        if (!$content instanceof ArrayObject) {
            return $pathItem;
        }

        $processedContent = $this->processContent($content);

        if ($processedContent === $content->getArrayCopy()) {
            return $pathItem;
        }

        $updatedOperation = $currentOperation->withRequestBody(
            $currentOperation->getRequestBody()->withContent(
                new ArrayObject($processedContent)
            )
        );

        return $pathItem->{'with' . $operation}($updatedOperation);
    }

    /**
     * @return array<string, mixed>
     */
    private function processContent(ArrayObject $content): array
    {
        $result = $content->getArrayCopy();

        foreach ($result as $mediaType => $mediaTypeObject) {
            if (!is_array($mediaTypeObject)) {
                continue;
            }

            $result[$mediaType] = $this->processMediaType($mediaTypeObject);
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $mediaTypeObject
     *
     * @return array<string, mixed>
     */
    private function processMediaType(array $mediaTypeObject): array
    {
        $properties = $mediaTypeObject['schema']['properties'] ?? [];

        if (!is_array($properties)) {
            return $mediaTypeObject;
        }

        $processedProperties = $properties;
        $wasModified = false;

        foreach ($properties as $propName => $propSchema) {
            if (!is_array($propSchema)) {
                continue;
            }

            if (($propSchema['type'] ?? null) === 'iri-reference') {
                $processedProperties[$propName] = array_merge(
                    $propSchema,
                    ['type' => 'string', 'format' => 'iri-reference']
                );
                $wasModified = true;
            }
        }

        if (!$wasModified) {
            return $mediaTypeObject;
        }

        return array_merge(
            $mediaTypeObject,
            ['schema' => array_merge($mediaTypeObject['schema'], ['properties' => $processedProperties])]
        );
    }
}
