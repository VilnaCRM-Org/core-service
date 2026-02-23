<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;

final class ConstraintViolationPayloadItemsProcessor
{
    public function process(OpenApi $openApi): OpenApi
    {
        $components = $openApi->getComponents();
        $schemas = $components->getSchemas();
        if ($schemas === null) {
            return $openApi;
        }
        $constraintViolationSchema = $schemas['ConstraintViolation'] ?? null;
        $constraintViolation = SchemaNormalizer::normalize($constraintViolationSchema);
        $violations = $constraintViolation['properties']['violations']['items'] ?? null;
        $properties = is_array($violations) ? ($violations['properties'] ?? null) : null;
        if (!is_array($properties)) {
            return $openApi;
        }
        $payload = $properties['payload'] ?? null;
        $payload = SchemaNormalizer::normalize($payload);
        if (!PayloadItemsRequirementChecker::shouldAddItems($payload)) {
            return $openApi;
        }
        $payload['items'] = ['type' => 'object'];
        $properties['payload'] = $payload;
        $constraintViolation['properties']['violations']['items']['properties'] =
            $properties;
        $schemas['ConstraintViolation'] = $constraintViolationSchema instanceof ArrayObject
            ? new ArrayObject($constraintViolation)
            : $constraintViolation;
        return $openApi->withComponents($components->withSchemas($schemas));
    }
}
