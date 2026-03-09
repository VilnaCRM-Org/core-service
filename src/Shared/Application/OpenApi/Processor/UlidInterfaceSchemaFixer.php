<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;

/**
 * Adds missing ulid property to UlidInterface.jsonld-output schema
 * and fixes ulid $ref to type: string in Customer and CustomerType schemas.
 */
final class UlidInterfaceSchemaFixer
{
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

        return $this->fixCustomerUlidRef($schemas, 'Customer.jsonld-output', 'CustomerType.jsonld-output');
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
    private function fixCustomerUlidRef(array $schemas, string ...$schemaNames): array
    {
        foreach ($schemaNames as $schemaName) {
            $schemas = $this->fixSingleSchemaUlidRef($schemas, $schemaName);
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

        if (! isset($schema['properties']['ulid'])) {
            return $schemas;
        }

        $ulidProp = $schema['properties']['ulid'];
        $ref = $ulidProp['$ref'] ?? null;

        if (! $this->isUlidInterfaceRef($ref)) {
            return $schemas;
        }

        $schema['properties']['ulid'] = ['type' => 'string'];
        $schemas[$schemaName] = $schema;

        return $schemas;
    }

    /**
     * @param array|bool|float|int|string|ArrayObject|null $ref
     */
    private function isUlidInterfaceRef(mixed $ref): bool
    {
        return is_string($ref) && str_contains($ref, 'UlidInterface');
    }
}
