<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\Fixture\SchemathesisFixtures;
use ArrayObject;

/**
 * @phpstan-type SchemaValue array|bool|float|int|object|string|null
 */
final class OpenApiInputSchemaUpdater
{
    private const CUSTOMER_TYPE_IRIS = [
        '/api/customer_types/' . SchemathesisFixtures::CUSTOMER_TYPE_ID,
        '/api/customer_types/' . SchemathesisFixtures::UPDATE_CUSTOMER_TYPE_ID,
    ];

    private const CUSTOMER_STATUS_IRIS = [
        '/api/customer_statuses/' . SchemathesisFixtures::CUSTOMER_STATUS_ID,
        '/api/customer_statuses/' . SchemathesisFixtures::UPDATE_CUSTOMER_STATUS_ID,
    ];

    private const INPUT_PROPERTY_SCHEMA_UPDATES = [
        'Customer.CustomerCreate' => [
            'initials' => ['minLength' => 1],
            'email' => ['minLength' => 1],
            'phone' => ['minLength' => 1],
            'leadSource' => ['minLength' => 1],
            'type' => [
                'format' => 'iri-reference',
                'enum' => self::CUSTOMER_TYPE_IRIS,
            ],
            'status' => [
                'format' => 'iri-reference',
                'enum' => self::CUSTOMER_STATUS_IRIS,
            ],
            'confirmed' => [],
        ],
        'Customer.CustomerPatch.jsonMergePatch' => [
            'type' => [
                'format' => 'iri-reference',
                'enum' => self::CUSTOMER_TYPE_IRIS,
            ],
            'status' => [
                'format' => 'iri-reference',
                'enum' => self::CUSTOMER_STATUS_IRIS,
            ],
        ],
        'Customer.CustomerPut' => [
            'initials' => ['minLength' => 1],
            'email' => ['minLength' => 1],
            'phone' => ['minLength' => 1],
            'leadSource' => ['minLength' => 1],
            'type' => [
                'format' => 'iri-reference',
                'enum' => self::CUSTOMER_TYPE_IRIS,
            ],
            'status' => [
                'format' => 'iri-reference',
                'enum' => self::CUSTOMER_STATUS_IRIS,
            ],
            'confirmed' => [],
        ],
        'CustomerStatus.StatusCreate' => [
            'value' => ['minLength' => 1],
        ],
        'CustomerStatus.StatusPut' => [
            'value' => ['minLength' => 1],
        ],
        'CustomerType.TypeCreate' => [
            'value' => ['minLength' => 1],
        ],
        'CustomerType.TypePut' => [
            'value' => ['minLength' => 1],
        ],
    ];

    private const REQUIRED_PROPERTIES_TO_ENFORCE = [
        'Customer.CustomerPut' => ['confirmed'],
    ];

    public function __construct(
        private readonly RequiredSchemaPropertyUpdater $propertyUpdater
    ) {
    }

    public function update(OpenApi $openApi): OpenApi
    {
        $components = $openApi->getComponents();
        $schemas = $components->getSchemas();

        if (! $schemas instanceof ArrayObject) {
            return $openApi;
        }

        $updatedSchemas = $this->updatedSchemas($schemas);

        return $updatedSchemas === null
            ? $openApi
            : $openApi->withComponents($components->withSchemas(new ArrayObject($updatedSchemas)));
    }

    /**
     * @return array<int|string, SchemaValue>|null
     */
    private function updatedSchemas(ArrayObject $schemas): ?array
    {
        $updatedSchemas = $schemas->getArrayCopy();
        $changed = false;
        foreach (self::INPUT_PROPERTY_SCHEMA_UPDATES as $schemaName => $propertySchemaUpdates) {
            $updatedSchema = $this->updatedSchemaFromCollection(
                $schemaName,
                $updatedSchemas,
                $propertySchemaUpdates
            );
            if ($updatedSchema === null) {
                continue;
            }
            $updatedSchemas[$schemaName] = $updatedSchema;
            $changed = true;
        }
        return $changed ? $updatedSchemas : null;
    }

    /**
     * @param array<string, SchemaValue> $schema
     * @param array<string, array<string, SchemaValue>> $propertySchemaUpdates
     *
     * @return array<string, SchemaValue>|null
     */
    private function updatedSchema(
        string $schemaName,
        array $schema,
        array $propertySchemaUpdates
    ): ?array {
        if ($schema === []) {
            return null;
        }

        [$updatedSchema, $requiredPropertiesChanged] = $this->updatedRequiredSchema(
            $schemaName,
            $schema
        );
        [$updatedSchema, $propertySchemasChanged] = $this->updatedPropertySchemas(
            $updatedSchema,
            $propertySchemaUpdates
        );
        return $requiredPropertiesChanged || $propertySchemasChanged ? $updatedSchema : null;
    }

    /**
     * @param array<int|string, SchemaValue> $schemas
     * @param array<string, array<string, SchemaValue>> $propertySchemaUpdates
     *
     * @return array<string, SchemaValue>|null
     */
    private function updatedSchemaFromCollection(
        string $schemaName,
        array $schemas,
        array $propertySchemaUpdates
    ): ?array {
        return $this->updatedSchema(
            $schemaName,
            SchemaNormalizer::normalize($schemas[$schemaName] ?? []),
            $propertySchemaUpdates
        );
    }

    /**
     * @param array<string, SchemaValue> $schema
     *
     * @return array{0: array<string, SchemaValue>, 1: bool}
     */
    private function updatedRequiredSchema(string $schemaName, array $schema): array
    {
        $updatedSchema = $this->ensureRequiredProperties(
            $schema,
            self::REQUIRED_PROPERTIES_TO_ENFORCE[$schemaName] ?? []
        );

        return $updatedSchema === null
            ? [$schema, false]
            : [$updatedSchema, true];
    }

    /**
     * @param array<string, SchemaValue> $schema
     * @param array<string, array<string, SchemaValue>> $propertySchemaUpdates
     *
     * @return array{0: array<string, SchemaValue>, 1: bool}
     */
    private function updatedPropertySchemas(
        array $schema,
        array $propertySchemaUpdates
    ): array {
        $updatedSchema = $schema;
        $changed = false;

        foreach ($propertySchemaUpdates as $propertyName => $schemaPatch) {
            [$updatedSchema, $propertyChanged] = $this->updatedPropertySchema(
                $updatedSchema,
                $propertyName,
                $schemaPatch
            );
            $changed = $changed || $propertyChanged;
        }

        return [$updatedSchema, $changed];
    }

    /**
     * @param array<string, SchemaValue> $schema
     * @param array<string, SchemaValue> $schemaPatch
     *
     * @return array{0: array<string, SchemaValue>, 1: bool}
     */
    private function updatedPropertySchema(
        array $schema,
        string $propertyName,
        array $schemaPatch
    ): array {
        $nonNullableSchema = $this->propertyUpdater->update($schema, $propertyName);
        $updatedSchema = $nonNullableSchema ?? $schema;
        $patchedSchema = $this->mergePropertySchemaPatch(
            $updatedSchema,
            $propertyName,
            $schemaPatch
        );

        if ($patchedSchema === null) {
            return [$updatedSchema, $nonNullableSchema !== null];
        }

        return [$patchedSchema, true];
    }

    /**
     * @param array<string, SchemaValue> $schema
     * @param array<int, string> $requiredProperties
     *
     * @return array<string, SchemaValue>|null
     */
    private function ensureRequiredProperties(
        array $schema,
        array $requiredProperties
    ): ?array {
        $currentRequiredProperties = SchemaNormalizer::normalize($schema['required'] ?? []);

        if (array_diff($requiredProperties, $currentRequiredProperties) === []) {
            return null;
        }

        $schema['required'] = array_values(
            array_unique([
                ...$currentRequiredProperties,
                ...$requiredProperties,
            ])
        );

        return $schema;
    }

    /**
     * @param array<string, SchemaValue> $schema
     * @param array<string, SchemaValue> $schemaPatch
     *
     * @return array<string, SchemaValue>|null
     */
    private function mergePropertySchemaPatch(
        array $schema,
        string $propertyName,
        array $schemaPatch
    ): ?array {
        $properties = SchemaNormalizer::normalize($schema['properties'] ?? []);
        $propertySchema = SchemaNormalizer::normalize($properties[$propertyName] ?? []);

        if ($propertySchema === []) {
            return null;
        }

        if (! $this->supportsPropertySchemaPatch($propertySchema, $schemaPatch)) {
            return null;
        }

        $updatedPropertySchema = array_replace($propertySchema, $schemaPatch);

        if ($updatedPropertySchema === $propertySchema) {
            return null;
        }

        $properties[$propertyName] = $updatedPropertySchema;
        $schema['properties'] = $properties;

        return $schema;
    }

    /**
     * @param array<string, SchemaValue> $propertySchema
     * @param array<string, SchemaValue> $schemaPatch
     */
    private function supportsPropertySchemaPatch(
        array $propertySchema,
        array $schemaPatch
    ): bool {
        $stringSpecificKeywords = ['minLength', 'format', 'enum'];

        if (array_intersect($stringSpecificKeywords, array_keys($schemaPatch)) === []) {
            return true;
        }

        $type = $propertySchema['type'] ?? null;

        if ($type === 'string') {
            return true;
        }

        if (! \is_array($type)) {
            return false;
        }

        return \in_array('string', $type, true)
            && array_diff($type, ['string', 'null']) === [];
    }
}
