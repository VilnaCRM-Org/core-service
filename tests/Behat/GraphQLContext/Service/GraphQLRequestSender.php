<?php

declare(strict_types=1);

namespace App\Tests\Behat\GraphQLContext\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

final class GraphQLRequestSender
{
    public function __construct(
        private readonly KernelInterface $kernel
    ) {
    }

    /**
     * @param array<string, mixed> $variables
     *
     * @return array{response: Response, data: array<string, mixed>|null}
     */
    public function send(string $query, array $variables = []): array
    {
        $requestData = ['query' => $query];
        if ($variables !== []) {
            $requestData['variables'] = $variables;
        }

        $request = Request::create(
            '/api/graphql',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($requestData)
        );

        $response = $this->kernel->handle($request);
        $content = $response->getContent();

        $data = null;
        if ($content !== false) {
            $data = json_decode($content, true);
        }

        return ['response' => $response, 'data' => $data];
    }
}
