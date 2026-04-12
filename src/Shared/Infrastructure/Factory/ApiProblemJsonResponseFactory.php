<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Factory;

use Symfony\Component\HttpFoundation\JsonResponse;

final class ApiProblemJsonResponseFactory
{
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
