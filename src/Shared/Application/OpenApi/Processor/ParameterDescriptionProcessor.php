<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Mapper\PathItemOperationMapper;
use App\Shared\Application\OpenApi\Mapper\PathsMapper;
use App\Shared\Application\OpenApi\ParameterDescriptionDictionary;

final class ParameterDescriptionProcessor
{
    public function process(OpenApi $openApi): OpenApi
    {
        $parameterDescriptions = ParameterDescriptionDictionary::descriptions();

        return PathsMapper::map(
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
        return PathItemOperationMapper::map(
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
            static fn (Parameter $parameter): Parameter => self::processParameter(
                $parameter,
                $descriptions
            ),
            $parameters
        );
    }

    /**
     * @param array<string, string> $descriptions
     */
    private static function processParameter(Parameter $parameter, array $descriptions): Parameter
    {
        $paramName = $parameter->getName();

        if (!isset($descriptions[$paramName]) || self::hasDescription($parameter)) {
            return $parameter;
        }

        return $parameter->withDescription($descriptions[$paramName]);
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
