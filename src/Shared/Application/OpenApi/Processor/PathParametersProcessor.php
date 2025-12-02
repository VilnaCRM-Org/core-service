<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Cleaner\PathParameterCleaner;
use App\Shared\Application\OpenApi\Cleaner\PathParameterCleanerInterface;

final class PathParametersProcessor
{
    private const OPERATIONS = ['Get', 'Post', 'Put', 'Patch', 'Delete'];

    private readonly PathParameterCleanerInterface $parameterCleaner;

    public function __construct(
        ?PathParameterCleanerInterface $parameterCleaner = null
    ) {
        $this->parameterCleaner = $parameterCleaner ?? new PathParameterCleaner();
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
        return array_reduce(
            self::OPERATIONS,
            fn (PathItem $item, string $op): PathItem => $this->updatePathItemOperation($item, $op),
            $pathItem
        );
    }

    private function updatePathItemOperation(PathItem $pathItem, string $operation): PathItem
    {
        $currentOperation = $pathItem->{'get' . $operation}();
        $parameters = $currentOperation?->getParameters();

        // Pattern: Combine null checks and inline trivial wrapper
        return $currentOperation instanceof Operation && is_array($parameters)
            ? $pathItem->{'with' . $operation}(
                $currentOperation->withParameters(
                    array_map($this->parameterCleaner->clean(...), $parameters)
                )
            )
            : $pathItem;
    }
}
