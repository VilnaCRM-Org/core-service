<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Mapper\PathItemOperationMapper;
use App\Shared\Application\OpenApi\Mapper\PathsMapper;
use App\Shared\Application\OpenApi\ParameterDescriptionDictionary;

final class ParameterDescriptionProcessor implements OpenApiProcessorInterface
{
    public function process(OpenApi $openApi): OpenApi
    {
        $parameterDescriptions = (new ParameterDescriptionDictionary())->descriptions();

        return (new PathsMapper())->map(
            $openApi,
            fn (PathItem $pathItem): PathItem => $this
                ->processPathItem($pathItem, $parameterDescriptions)
        );
    }

    /**
     * @param array<string, string> $descriptions
     */
    private function processPathItem(PathItem $pathItem, array $descriptions): PathItem
    {
        return (new PathItemOperationMapper())->map(
            $pathItem,
            fn ($operation) => $operation->withParameters(
                $this->processParameters($operation->getParameters(), $descriptions)
            )
        );
    }

    /**
     * @param array<int, Parameter> $parameters
     * @param array<string, string> $descriptions
     *
     * @return array<int, Parameter>
     */
    private function processParameters(array $parameters, array $descriptions): array
    {
        return array_map(
            fn (Parameter $parameter): Parameter => $this->processParameter(
                $parameter,
                $descriptions
            ),
            $parameters
        );
    }

    /**
     * @param array<string, string> $descriptions
     */
    private function processParameter(Parameter $parameter, array $descriptions): Parameter
    {
        $paramName = $parameter->getName();

        return match (true) {
            ! isset($descriptions[$paramName]) => $parameter,
            $this->hasDescription($parameter) => $parameter,
            default => $parameter->withDescription($descriptions[$paramName]),
        };
    }

    private function hasDescription(Parameter $parameter): bool
    {
        return ! $this->isDescriptionEmpty($parameter->getDescription());
    }

    private function isDescriptionEmpty(?string $description): bool
    {
        return $description === null || $description === '';
    }
}
