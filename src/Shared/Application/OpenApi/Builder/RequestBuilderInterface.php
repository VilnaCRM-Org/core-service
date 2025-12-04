<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Builder;

use ApiPlatform\OpenApi\Model\RequestBody;
use App\Shared\Application\OpenApi\ValueObject\Parameter;

interface RequestBuilderInterface
{
    /**
     * @param array<Parameter> $params
     */
    public function build(
        array $params,
        bool $required = true,
        ?string $contentType = null
    ): RequestBody;

    /**
     * @param array<Parameter> $params
     */
    public function buildRequired(array $params, ?string $contentType = null): RequestBody;

    /**
     * @param array<Parameter> $params
     */
    public function buildOptional(array $params, ?string $contentType = null): RequestBody;
}
