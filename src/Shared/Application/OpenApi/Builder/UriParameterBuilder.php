<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Builder;

use ApiPlatform\OpenApi\Model\Parameter;

final class UriParameterBuilder
{
    public function build(
        string $name,
        string $description,
        bool $required,
        string $example,
        string $type,
        ?array $enum = null
    ): Parameter {
        $schema = [
            'type' => $type,
            'default' => $example,
            'example' => $example,
        ];

        if (\is_array($enum) && $enum !== []) {
            $schema['enum'] = $enum;
        }

        return new Parameter(
            name: $name,
            in: 'path',
            description: $description,
            required: $required,
            schema: $schema,
            example: $example
        );
    }
}
