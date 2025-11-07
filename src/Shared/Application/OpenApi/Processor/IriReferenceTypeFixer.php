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
        $content = $operation?->getRequestBody()?->getContent();

        if (!$content instanceof ArrayObject) {
            return $operation;
        }

        $modified = $this->fixContentProperties($content);

        return $modified
            ? $operation->withRequestBody(
                $operation->getRequestBody()->withContent(new ArrayObject($content->getArrayCopy()))
            )
            : $operation;
    }

    private function fixContentProperties(ArrayObject $content): bool
    {
        $modified = false;

        foreach ($content as $mediaType => $mediaTypeObject) {
            foreach ($mediaTypeObject['schema']['properties'] ?? [] as $propName => $propSchema) {
                if (($propSchema['type'] ?? null) === 'iri-reference') {
                    $content[$mediaType]['schema']['properties'][$propName] = array_merge(
                        $propSchema,
                        ['type' => 'string', 'format' => 'iri-reference']
                    );
                    $modified = true;
                }
            }
        }

        return $modified;
    }
}
