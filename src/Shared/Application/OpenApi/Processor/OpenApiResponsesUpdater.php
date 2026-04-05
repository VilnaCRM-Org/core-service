<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Response;

final class OpenApiResponsesUpdater
{
    public function __construct(
        private OpenApiResponseSchemaFixer $responseSchemaFixer
    ) {
    }

    /**
     * @param array<int|string, Response|array>|null $responses
     *
     * @return array<int|string, Response|array>|null
     */
    public function update(?array $responses): ?array
    {
        if ($responses === null) {
            return null;
        }

        $updatedResponses = $this->updatedResponses($responses);

        return $updatedResponses === $responses ? null : $updatedResponses;
    }

    /**
     * @param array<int|string, Response|array> $responses
     *
     * @return array<int|string, Response|array>
     */
    private function updatedResponses(array $responses): array
    {
        return array_map(
            $this->responseSchemaFixer->fix(...),
            $responses
        );
    }
}
