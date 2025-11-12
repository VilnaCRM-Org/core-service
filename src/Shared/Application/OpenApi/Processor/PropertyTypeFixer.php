<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

/**
 * Fixes property type from 'iri-reference' to proper string format.
 */
final class PropertyTypeFixer
{
    /**
     * @param array<string, string|int|float|bool|array|null> $propSchema
     *
     * @return array<string, string|int|float|bool|array|null>
     */
    public function fix(array $propSchema): array
    {
        return array_merge(
            $propSchema,
            ['type' => 'string', 'format' => 'iri-reference']
        );
    }

    /**
     * @param array<string, string|int|float|bool|array|null> $propSchema
     */
    public function needsFix(array $propSchema): bool
    {
        return ($propSchema['type'] ?? null) === 'iri-reference';
    }
}
