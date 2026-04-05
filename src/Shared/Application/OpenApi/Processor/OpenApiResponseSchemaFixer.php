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
        if (! $response instanceof Response) {
            return $response;
        }

        $content = $response->getContent();
        if ($content === null) {
            return $response;
        }

        $updatedContent = $this->contentUpdater->update($content);
        if ($updatedContent === null) {
            return $response;
        }

        return $response->withContent($updatedContent);
    }
}
