<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;

/**
 * @phpstan-type SchemaValue array|bool|float|int|object|string|null
 */
final class OpenApiInputSchemaUpdater
{
    private const REQUIRED_NON_NULLABLE_PROPERTIES = [
        'Customer.CustomerCreate' => 'confirmed',
        'CustomerType.TypeCreate' => 'value',
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

        foreach (self::REQUIRED_NON_NULLABLE_PROPERTIES as $schemaName => $propertyName) {
            $updatedSchema = $this->propertyUpdater->update(
                SchemaNormalizer::normalize($updatedSchemas[$schemaName] ?? []),
                $propertyName
            );

            if ($updatedSchema === null) {
                continue;
            }

            $updatedSchemas[$schemaName] = $updatedSchema;
            $changed = true;
        }

        return $changed
            ? $updatedSchemas
            : null;
    }
}
