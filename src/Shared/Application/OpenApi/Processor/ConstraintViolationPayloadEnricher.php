<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ArrayObject;

/**
 * @phpstan-type SchemaValue array|bool|float|int|string|ArrayObject|null
 */
final class ConstraintViolationPayloadEnricher
{
    /**
     * @param array<string, SchemaValue> $properties
     *
     * @return array<string, SchemaValue>|null
     */
    public function enrich(array $properties): ?array
    {
        $payload = (new ConstraintViolationPayloadBuilder())->build($properties);
        if ($payload === null) {
            return null;
        }

        $properties += ['code' => $this->defaultCodeProperty()];
        $properties['payload'] = $payload;

        return $properties;
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    private function defaultCodeProperty(): array
    {
        return [
            'type' => ['string', 'null'],
            'description' => 'The machine-readable violation code',
        ];
    }
}
