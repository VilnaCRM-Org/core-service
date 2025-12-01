<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Cleaner\PathParameterCleaner;

final class PathParametersProcessor
{
    private const OPERATIONS = ['Get', 'Post', 'Put', 'Patch', 'Delete'];

    public function __construct(
        private readonly PathParameterCleaner $parameterCleaner = new PathParameterCleaner()
    ) {
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
        foreach (self::OPERATIONS as $operation) {
            $pathItem = $this->updatePathItemOperation($pathItem, $operation);
        }

        return $pathItem;
    }

    private function updatePathItemOperation(PathItem $pathItem, string $operation): PathItem
    {
        $currentOperation = $pathItem->{'get' . $operation}();

        if (!$currentOperation instanceof Operation) {
            return $pathItem;
        }

        return $pathItem->{'with' . $operation}(
            $this->processOperation($currentOperation)
        );
    }

    private function processOperation(Operation $operation): Operation
    {
        $parameters = $operation->getParameters();

        if (!is_array($parameters)) {
            return $operation;
        }

        $cleanedParameters = array_map(
            $this->processParameter(...),
            $parameters
        );

        return $operation->withParameters($cleanedParameters);
    }

    private function processParameter(Parameter|array $parameter): Parameter|array
    {
        return $this->parameterCleaner->clean($parameter);
    }
}
