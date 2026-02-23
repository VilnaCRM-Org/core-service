<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\OpenApi;

final class ConstraintViolationPayloadItemsProcessor
{
    public function process(OpenApi $openApi): OpenApi
    {
        $components = $openApi->getComponents();
        $schemas = $components->getSchemas();
        if ($schemas === null) {
            return $openApi;
        }

        $updatedSchema = $this->withPayloadItems($schemas['ConstraintViolation'] ?? null);
        if ($updatedSchema === null) {
            return $openApi;
        }

        $schemas['ConstraintViolation'] = $updatedSchema;

        return $openApi->withComponents($components->withSchemas($schemas));
    }

    private function withPayloadItems(mixed $constraintViolationSchema): array|ArrayObject|null
    {
        $constraintViolation = SchemaNormalizer::normalize($constraintViolationSchema);
        $properties = $this->extractViolationProperties($constraintViolation);
        $payload = SchemaNormalizer::normalize($properties['payload'] ?? null);

        if ($properties === null || !PayloadItemsRequirementChecker::shouldAddItems($payload)) {
            return null;
        }

        $payload['items'] = ['type' => 'object'];
        $properties['payload'] = $payload;
        $constraintViolation['properties']['violations']['items']['properties'] = $properties;

        return $constraintViolationSchema instanceof ArrayObject
            ? new ArrayObject($constraintViolation)
            : $constraintViolation;
    }

    /**
     * @param array<string, mixed> $constraintViolation
     * @return array<string, mixed>|null
     */
    private function extractViolationProperties(array $constraintViolation): ?array
    {
        $violations = $constraintViolation['properties']['violations']['items'] ?? null;
        $properties = is_array($violations) ? ($violations['properties'] ?? null) : null;

        return is_array($properties) ? $properties : null;
    }
}
