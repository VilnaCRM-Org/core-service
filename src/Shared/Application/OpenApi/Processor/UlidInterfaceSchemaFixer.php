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
        $customerSchemas = ['Customer.jsonld-output', 'CustomerType.jsonld-output'];

        return $this->fixCustomerUlidRef($schemas, ...$customerSchemas);
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
            $schema = $schemas[$schemaName] ?? [];

            if (! isset($schema['properties']['ulid'])) {
                continue;
            }

            $ulidProp = $schema['properties']['ulid'];
            $ref = $ulidProp['$ref'] ?? null;

            if (! is_string($ref) || ! str_contains($ref, 'UlidInterface')) {
                continue;
            }

            $schema['properties']['ulid'] = ['type' => 'string'];
            $schemas[$schemaName] = $schema;
        }

        return $schemas;
    }
}
