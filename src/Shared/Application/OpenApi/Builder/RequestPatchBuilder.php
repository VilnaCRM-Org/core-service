<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Builder;

use ApiPlatform\OpenApi\Model\RequestBody;
use App\Shared\Application\OpenApi\ValueObject\Parameter;

final class RequestPatchBuilder implements RequestBuilderInterface
{
    public function __construct(private ContextBuilder $contextBuilder)
    {
    }

    /**
     * @param array<Parameter> $params
     */
    public function build(
        array $params,
        bool $required = true,
        ?string $contentType = null
    ): RequestBody {
        $content = $this->contextBuilder->build(
            $params,
            $contentType ?? 'application/merge-patch+json'
        );

        return new RequestBody(
            content: $content,
            required: $required
        );
    }

    /**
     * @param array<Parameter> $params
     */
    public function buildRequired(
        array $params,
        ?string $contentType = null
    ): RequestBody {
        return $this->build($params, true, $contentType);
    }

    /**
     * @param array<Parameter> $params
     */
    public function buildOptional(
        array $params,
        ?string $contentType = null
    ): RequestBody {
        return $this->build($params, false, $contentType);
    }
}
