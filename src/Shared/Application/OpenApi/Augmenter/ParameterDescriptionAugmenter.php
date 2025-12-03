<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Augmenter;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\ParameterDescriptionDictionary;
use App\Shared\Application\OpenApi\Support\PathItemOperations;
use App\Shared\Application\OpenApi\Support\PathsManipulator;

final class ParameterDescriptionAugmenter
{
    public function augment(OpenApi $openApi): void
    {
        $parameterDescriptions = ParameterDescriptionDictionary::descriptions();

        PathsManipulator::map(
            $openApi,
            fn (PathItem $pathItem): PathItem => $this
                ->augmentPathItem($pathItem, $parameterDescriptions)
        );
    }

    /**
     * @param array<string, string> $descriptions
     */
    private function augmentPathItem(PathItem $pathItem, array $descriptions): PathItem
    {
        return PathItemOperations::map(
            $pathItem,
            fn (Operation $operation): Operation => $this
                ->augmentOperation($operation, $descriptions)
        );
    }

    /**
     * @param array<string, string> $descriptions
     */
    private function augmentOperation(Operation $operation, array $descriptions): Operation
    {
        return $operation->getParameters() === []
            ? $operation
            : $operation->withParameters(
                $this->augmentParameters($operation->getParameters(), $descriptions)
            );
    }

    /**
     * @param array<int, Parameter> $parameters
     * @param array<string, string> $descriptions
     *
     * @return array<int, Parameter>
     */
    private function augmentParameters(array $parameters, array $descriptions): array
    {
        return array_map(
            static fn (Parameter $parameter): Parameter => self::augmentParameter(
                $parameter,
                $descriptions
            ),
            $parameters
        );
    }

    /**
     * @param array<string, string> $descriptions
     */
    private static function augmentParameter(Parameter $parameter, array $descriptions): Parameter
    {
        $paramName = $parameter->getName();

        return match (true) {
            !isset($descriptions[$paramName]) => $parameter,
            self::hasDescription($parameter) => $parameter,
            default => $parameter->withDescription($descriptions[$paramName]),
        };
    }

    private static function hasDescription(Parameter $parameter): bool
    {
        return !self::isDescriptionEmpty($parameter->getDescription());
    }

    private static function isDescriptionEmpty(?string $description): bool
    {
        return ($description ?? '') === '';
    }
}
