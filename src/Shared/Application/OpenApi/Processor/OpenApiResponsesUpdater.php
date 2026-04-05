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
    public function update($responses)
    {
        return match (true) {
            $responses === null => null,
            ($updatedResponses = $this->updatedResponses($responses)) === $responses => null,
            default => $updatedResponses,
        };
    }

    /**
     * @param array<int|string, Response|array> $responses
     *
     * @return array<int|string, Response|array>
     */
    private function updatedResponses($responses)
    {
        foreach ($responses as $statusCode => $response) {
            $updatedResponse = $this->responseSchemaFixer->fix($response);
            if ($updatedResponse === $response) {
                continue;
            }

            $responses[$statusCode] = $updatedResponse;
        }

        return $responses;
    }
}
