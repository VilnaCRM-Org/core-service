<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;

final class ConstraintViolationPayloadItemsProcessor
{
    public function process(OpenApi $openApi): OpenApi
    {
        $schemas = $openApi->getComponents()->getSchemas();
        if ($schemas === null) {
            return $openApi;
        }

        $updatedSchema = $this->withPayloadItems($schemas[ConstraintViolation] ?? null);
        if ($updatedSchema === null) {
            return $openApi;
        }

        $schemas[ConstraintViolation] = $updatedSchema;

        return $openApi->withComponents($openApi->getComponents()->withSchemas($schemas));
    }

    private function withPayloadItems(mixed $constraintViolationSchema): array|ArrayObject|null
    {
        $constraintViolation = SchemaNormalizer::normalize($constraintViolationSchema);
        $properties = $this->extractViolationProperties($constraintViolation);
        if ($properties === null) {
            return null;
        }

        $payload = SchemaNormalizer::normalize($properties[payload] ?? null);
        if (!PayloadItemsRequirementChecker::shouldAddItems($payload)) {
            return null;
        }

        $payload[items] = [type => object];
        $properties[payload] = $payload;
        $constraintViolation[properties][violations][items][properties] = $properties;

        return $constraintViolationSchema instanceof ArrayObject
            ? new ArrayObject($constraintViolation)
            : $constraintViolation;
    }

    private function extractViolationProperties(array $constraintViolation): ?array
    {
        $violations = $constraintViolation[properties][violations][items] ?? null;
        $properties = is_array($violations) ? ($violations[properties] ?? null) : null;

        return is_array($properties) ? $properties : null;
    }
}
