<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Response;

final class OpenApiResponseSchemaFixer
{
    public function __construct(
        private OpenApiResponseContentUpdater $contentUpdater
    ) {
    }

    public function fix(Response|array $response): Response|array
    {
        return match (true) {
            ! $response instanceof Response => $response,
            ($content = $response->getContent()) === null => $response,
            ($updatedContent = $this->contentUpdater->update($content)) === null => $response,
            default => $response->withContent($updatedContent),
        };
    }
}
