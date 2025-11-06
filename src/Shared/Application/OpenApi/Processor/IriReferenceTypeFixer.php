<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;

final class IriReferenceTypeFixer
{
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
        return $pathItem
            ->withGet($this->fixOperation($pathItem->getGet()))
            ->withPost($this->fixOperation($pathItem->getPost()))
            ->withPut($this->fixOperation($pathItem->getPut()))
            ->withPatch($this->fixOperation($pathItem->getPatch()))
            ->withDelete($this->fixOperation($pathItem->getDelete()));
    }

    private function fixOperation(?Operation $operation): ?Operation
    {
        if ($operation === null) {
            return null;
        }

        $requestBody = $operation->getRequestBody();
        if ($requestBody === null) {
            return $operation;
        }

        $content = $requestBody->getContent();
        if ($content === null) {
            return $operation;
        }

        $modified = false;
        foreach ($content as $mediaType => $mediaTypeObject) {
            if (!isset($mediaTypeObject['schema'])) {
                continue;
            }

            $schema = $mediaTypeObject['schema'];
            if (isset($schema['properties'])) {
                foreach ($schema['properties'] as $propName => $propSchema) {
                    if (isset($propSchema['type']) && $propSchema['type'] === 'iri-reference') {
                        $schema['properties'][$propName]['type'] = 'string';
                        $schema['properties'][$propName]['format'] = 'iri-reference';
                        $modified = true;
                    }
                }
            }

            if ($modified) {
                $content[$mediaType]['schema'] = $schema;
            }
        }

        if ($modified) {
            $requestBody = $requestBody->withContent(new ArrayObject($content->getArrayCopy()));
            return $operation->withRequestBody($requestBody);
        }

        return $operation;
    }
}
