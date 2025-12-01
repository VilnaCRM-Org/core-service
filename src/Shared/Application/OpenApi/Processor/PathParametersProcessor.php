<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Cleaner\PathParameterCleaner;

final class PathParametersProcessor
{
    private readonly PathParameterCleaner $parameterCleaner;

    public function __construct(
        PathParameterCleaner $parameterCleaner = new PathParameterCleaner()
    ) {
        $this->parameterCleaner = $parameterCleaner;
    }

    public function process(OpenApi $openApi): OpenApi
    {
        $paths = $openApi->getPaths();

        foreach (array_keys($paths->getPaths()) as $path) {
            $pathItem = $paths->getPath($path);
            $paths->addPath($path, $this->processPathItem($pathItem));
        }

        return $openApi;
    }

    private function processPathItem(PathItem $pathItem): PathItem
    {
        $processedPathItem = $pathItem;

        $processedPathItem = $processedPathItem->withGet(
            $this->processOperation($pathItem->getGet())
        );
        $processedPathItem = $processedPathItem->withPost(
            $this->processOperation($pathItem->getPost())
        );
        $processedPathItem = $processedPathItem->withPut(
            $this->processOperation($pathItem->getPut())
        );
        $processedPathItem = $processedPathItem->withPatch(
            $this->processOperation($pathItem->getPatch())
        );
        $processedPathItem = $processedPathItem->withDelete(
            $this->processOperation($pathItem->getDelete())
        );

        return $processedPathItem;
    }

    private function processOperation(?Operation $operation): ?Operation
    {
        return match (true) {
            $operation === null => null,
            !\is_array($operation->getParameters()) => $operation,
            default => $operation->withParameters(
                array_map(
                    $this->processParameter(...),
                    $operation->getParameters()
                )
            ),
        };
    }

    private function processParameter(\ApiPlatform\OpenApi\Model\Parameter|array $parameter): mixed
    {
        return $this->parameterCleaner->clean($parameter);
    }
}
