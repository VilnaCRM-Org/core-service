<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Builder;

final class ParameterSchemaFactory
{
    /**
     * @return array<string, string|int|array<string, string>>
     */
    public function create(Parameter $param): array
    {
        return array_filter(
            [
                'type' => $param->type,
                'maxLength' => $param->maxLength,
                'format' => $param->format,
                'items' => $param->items,
            ],
            static fn ($value) => $value !== null
        );
    }
}
