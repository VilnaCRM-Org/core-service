<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;

/**
 * Adds missing ulid property to UlidInterface.jsonld-output schema
 * and fixes ulid $ref to type: string in Customer schemas.
 */
final class UlidInterfaceSchemaFixer
{
    private const CUSTOMER_SCHEMAS = ['Customer.jsonld-output', 'CustomerType.jsonld-output'];

    public function process(OpenApi $openApi): OpenApi
    {
        $components = $openApi->getComponents();
        $schemas = $components->getSchemas();

        if ($schemas === null) {
            return $openApi;
        }

        $schemasArray = $schemas->getArrayCopy();
        $schemasArray = $this->addUlidProperty($schemasArray);

        return $openApi->withComponents($components->withSchemas(new ArrayObject($schemasArray)));
    }

    /**
     * @param array<string, array|bool|float|int|string|ArrayObject|null> $schemas
     *
     * @return array<string, array|bool|float|int|string|ArrayObject|null>
     */
    private function addUlidProperty(array $schemas): array
    {
        $schemas = $this->addUlidToInterfaceSchema($schemas);

        foreach (self::CUSTOMER_SCHEMAS as $schemaName) {
            $schemas = $this->fixSingleSchemaUlidRef($schemas, $schemaName);
        }

        return $schemas;
    }

    /**
     * @param array<string, array|bool|float|int|string|ArrayObject|null> $schemas
     *
     * @return array<string, array|bool|float|int|string|ArrayObject|null>
     */
    private function addUlidToInterfaceSchema(array $schemas): array
    {
        $ulidInterface = $schemas['UlidInterface.jsonld-output'] ?? [];

        if (is_array($ulidInterface) && ! isset($ulidInterface['properties']['ulid'])) {
            $ulidInterface['properties']['ulid'] = ['type' => 'string'];
            $schemas['UlidInterface.jsonld-output'] = $ulidInterface;
        }

        return $schemas;
    }

    /**
     * @param array<string, array|bool|float|int|string|ArrayObject|null> $schemas
     *
     * @return array<string, array|bool|float|int|string|ArrayObject|null>
     */
    private function fixSingleSchemaUlidRef(array $schemas, string $schemaName): array
    {
        $schema = $schemas[$schemaName] ?? [];

        if (! $this->hasUlidProperty($schema)) {
            return $schemas;
        }

        $ulidProp = $schema['properties']['ulid'];

        if (! $this->hasUlidInterfaceRef($ulidProp)) {
            return $schemas;
        }

        $schema['properties']['ulid'] = ['type' => 'string'];
        $schemas[$schemaName] = $schema;

        return $schemas;
    }

    private function hasUlidProperty(array|ArrayObject|string|int|bool|float|null $schema): bool
    {
        if (! is_array($schema) && ! $schema instanceof ArrayObject) {
            return false;
        }

        return isset($schema['properties']['ulid']);
    }

    private function hasUlidInterfaceRef(array|ArrayObject|string|int|bool|float|null $ulidProp): bool
    {
        $ref = is_array($ulidProp)
            ? ($ulidProp['$ref'] ?? null)
            : ($ulidProp instanceof ArrayObject ? ($ulidProp['$ref'] ?? null) : null);

        if (! is_string($ref)) {
            return false;
        }

        return str_contains($ref, 'UlidInterface');
    }
}
