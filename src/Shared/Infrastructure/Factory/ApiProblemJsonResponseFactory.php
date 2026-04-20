<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Factory;

use Symfony\Component\HttpFoundation\JsonResponse;

final class ApiProblemJsonResponseFactory
{
    public function createBadRequestResponse(
        string $detail = 'Invalid request payload.',
    ): JsonResponse {
        $response = new JsonResponse(
            [
                'title' => 'An error occurred',
                'detail' => $detail,
                'status' => JsonResponse::HTTP_BAD_REQUEST,
                'type' => '/errors/400',
            ],
            JsonResponse::HTTP_BAD_REQUEST
        );
        $response->headers->set('Content-Type', 'application/problem+json; charset=utf-8');

        return $response;
    }

    public function createNotFoundResponse(): JsonResponse
    {
        $response = new JsonResponse(
            [
                'title' => 'An error occurred',
                'detail' => 'Not Found',
                'status' => JsonResponse::HTTP_NOT_FOUND,
                'type' => '/errors/404',
            ],
            JsonResponse::HTTP_NOT_FOUND
        );
        $response->headers->set('Content-Type', 'application/problem+json; charset=utf-8');

        return $response;
    }
}
