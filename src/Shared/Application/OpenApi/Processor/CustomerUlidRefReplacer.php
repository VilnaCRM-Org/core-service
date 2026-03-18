<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ArrayObject;

final class CustomerUlidRefReplacer
{
    /**
     * @param array<string, array|bool|float|int|string|ArrayObject|null> $schemas
     *
     * @return array<string, array|bool|float|int|string|ArrayObject|null>
     */
    public function replace(array $schemas, string $schemaName): array
    {
        $schema = $this->toArray($schemas[$schemaName] ?? []);
        $properties = $this->toArray($schema['properties'] ?? []);
        $ulidProperty = $this->toArray($properties['ulid'] ?? []);
        $ref = $ulidProperty['$ref'] ?? null;

        if (! is_string($ref) || ! str_contains($ref, 'UlidInterface')) {
            return $schemas;
        }

        $properties['ulid'] = ['type' => 'string'];
        $schema['properties'] = $properties;
        $schemas[$schemaName] = $schema;

        return $schemas;
    }

    /**
     * @return array<string, array|bool|float|int|string|null>
     */
    private function toArray(ArrayObject|array|string|int|float|bool|null $value): array
    {
        if ($value instanceof ArrayObject) {
            return $value->getArrayCopy();
        }

        return is_array($value) ? $value : [];
    }
}
