<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;

final class IriReferenceTypeFixer
{
    private const OPERATIONS = ['Get', 'Post', 'Put', 'Patch', 'Delete'];

    public function fix(OpenApi $openApi): void
    {
        foreach (array_keys($openApi->getPaths()->getPaths()) as $path) {
            $pathItem = $openApi->getPaths()->getPath($path);
            $openApi->getPaths()->addPath(
                $path,
                $this->fixPathItem($pathItem)
            );
        }
    }

    private function fixPathItem(PathItem $pathItem): PathItem
    {
        foreach (self::OPERATIONS as $operation) {
            $pathItem = $pathItem->{'with' . $operation}(
                $this->fixOperation($pathItem->{'get' . $operation}())
            );
        }

        return $pathItem;
    }

    private function fixOperation(?Operation $operation): ?Operation
    {
        return match (true) {
            $operation === null => null,
            $operation->getRequestBody() === null => $operation,
            $operation->getRequestBody()->getContent() === null => $operation,
            default => $this->processOperationContent($operation),
        };
    }

    private function processOperationContent(Operation $operation): Operation
    {
        $requestBody = $operation->getRequestBody();
        $content = $requestBody->getContent();
        $modified = false;

        foreach ($content as $mediaType => $mediaTypeObject) {
            $fixedProperties = $this->fixProperties($mediaTypeObject);
            if ($fixedProperties !== null) {
                $content[$mediaType]['schema']['properties'] = $fixedProperties;
                $modified = true;
            }
        }

        return $modified
            ? $operation->withRequestBody(
                $requestBody->withContent(new ArrayObject($content->getArrayCopy()))
            )
            : $operation;
    }

    /**
     * @param array<string, mixed> $mediaTypeObject
     *
     * @return array<string, mixed>|null
     */
    private function fixProperties(array $mediaTypeObject): ?array
    {
        if (!isset($mediaTypeObject['schema']['properties'])) {
            return null;
        }

        $properties = $mediaTypeObject['schema']['properties'];
        $fixedProperties = array_map(
            static fn ($propSchema) => self::fixProperty($propSchema),
            $properties
        );

        return $fixedProperties === $properties ? null : $fixedProperties;
    }

    /**
     * @param array<string, mixed> $propSchema
     *
     * @return array<string, mixed>
     */
    private static function fixProperty(array $propSchema): array
    {
        return match (true) {
            !isset($propSchema['type']) => $propSchema,
            $propSchema['type'] !== 'iri-reference' => $propSchema,
            default => array_merge($propSchema, [
                'type' => 'string',
                'format' => 'iri-reference',
            ]),
        };
    }
}
