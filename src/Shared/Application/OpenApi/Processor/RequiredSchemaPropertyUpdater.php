<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

/**
 * @phpstan-type SchemaValue array|bool|float|int|object|string|null
 */
final class RequiredSchemaPropertyUpdater
{
    public function __construct(
        private readonly NullableSchemaTypeNormalizer $typeNormalizer
    ) {
    }

    /**
     * @param array<int|string, SchemaValue> $schema
     *
     * @return array<int|string, SchemaValue>|null
     */
    public function update(array $schema, string $propertyName): ?array
    {
        if (! \in_array(
            $propertyName,
            SchemaNormalizer::normalize($schema['required'] ?? []),
            true
        )) {
            return null;
        }

        $properties = SchemaNormalizer::normalize($schema['properties'] ?? []);
        $updatedProperty = $this->typeNormalizer->normalize(
            SchemaNormalizer::normalize($properties[$propertyName] ?? [])
        );

        if ($updatedProperty === null) {
            return null;
        }

        $properties[$propertyName] = $updatedProperty;
        $schema['properties'] = $properties;

        return $schema;
    }
}
