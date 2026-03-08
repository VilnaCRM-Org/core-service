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

        $schemas = $this->addUlidPropertyToUlidInterface($schemas);
        $schemas = $this->fixUlidRefInCustomerSchemas($schemas);

        return $openApi->withComponents($components->withSchemas($schemas));
    }

    /**
     * @param ArrayObject<string, array|bool|float|int|string|ArrayObject|null> $schemas
     *
     * @return ArrayObject<string, array|bool|float|int|string|ArrayObject|null>
     */
    private function addUlidPropertyToUlidInterface(ArrayObject $schemas): ArrayObject
    {
        $schemas = $schemas->getArrayCopy();

        if (! isset($schemas['UlidInterface.jsonld-output'])) {
            return new ArrayObject($schemas);
        }

        $ulidInterface = $schemas['UlidInterface.jsonld-output'];

        if (! is_array($ulidInterface)) {
            return new ArrayObject($schemas);
        }

        // Check if ulid property already exists
        if (isset($ulidInterface['properties']['ulid'])) {
            return new ArrayObject($schemas);
        }

        // Add ulid property after @type
        $ulidInterface['properties']['ulid'] = [
            'type' => 'string',
        ];

        $schemas['UlidInterface.jsonld-output'] = $ulidInterface;

        return new ArrayObject($schemas);
    }

    /**
     * @param ArrayObject<string, array|bool|float|int|string|ArrayObject|null> $schemas
     *
     * @return ArrayObject<string, array|bool|float|int|string|ArrayObject|null>
     */
    private function fixUlidRefInCustomerSchemas(ArrayObject $schemas): ArrayObject
    {
        $schemas = $schemas->getArrayCopy();

        foreach (['Customer.jsonld-output', 'CustomerType.jsonld-output'] as $schemaName) {
            if (! isset($schemas[$schemaName])) {
                continue;
            }

            $schema = $schemas[$schemaName];

            if (! is_array($schema) || ! isset($schema['properties']['ulid'])) {
                continue;
            }

            $ulidProperty = $schema['properties']['ulid'];

            // Check if ulid has $ref to UlidInterface and fix it
            if (isset($ulidProperty['$ref']) && str_contains($ulidProperty['$ref'], 'UlidInterface')) {
                $schema['properties']['ulid'] = [
                    'type' => 'string',
                ];
                $schemas[$schemaName] = $schema;
            }
        }

        return new ArrayObject($schemas);
    }
}
