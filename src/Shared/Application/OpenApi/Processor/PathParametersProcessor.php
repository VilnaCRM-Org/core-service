<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Cleaner\PathParameterCleaner;
use App\Shared\Application\OpenApi\Cleaner\PathParameterCleanerInterface;
use App\Shared\Application\OpenApi\Support\PathItemOperations;
use App\Shared\Application\OpenApi\Support\PathsManipulator;

final class PathParametersProcessor
{
    private readonly PathParameterCleanerInterface $parameterCleaner;

    public function __construct(
        ?PathParameterCleanerInterface $parameterCleaner = null
    ) {
        $this->parameterCleaner = $parameterCleaner ?? new PathParameterCleaner();
    }

    public function process(OpenApi $openApi): OpenApi
    {
        PathsManipulator::map(
            $openApi,
            fn (PathItem $pathItem): PathItem => $this->processPathItem($pathItem)
        );

        return $openApi;
    }

    private function processPathItem(PathItem $pathItem): PathItem
    {
        return PathItemOperations::map(
            $pathItem,
            fn (Operation $operation): Operation => $this
                ->updateOperation($operation)
        );
    }

    private function updateOperation(Operation $operation): Operation
    {
        $parameters = $operation->getParameters();

        return is_array($parameters)
            ? $operation->withParameters(
                array_map($this->parameterCleaner->clean(...), $parameters)
            )
            : $operation;
    }
}
