<?php

declare(strict_types=1);

namespace App\Tests\Integration\Negative;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Integration\Negative\Kernel\NegativeKernel;
use App\Tests\Unit\UlidProvider;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\HttpFoundation\Response;

abstract class BaseNegativeApiTest extends ApiTestCase
{
    protected Generator $faker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = $this->getContainer();
        $this->faker = Factory::create();
        $this->faker->addProvider(new UlidProvider($this->faker));
    }

    protected static function getKernelClass(): string
    {
        return NegativeKernel::class;
    }

    protected function sendRequest(
        string $method,
        string $uri,
        array $payload = [],
        array $headers = [],
        int $expectedStatusCode = Response::HTTP_INTERNAL_SERVER_ERROR,
        string $contentType = 'application/ld+json'
    ): void {
        $client = static::createClient();

        $options = [
            'headers' => array_merge(
                [
                    'Content-Type' => $contentType,
                ],
                $headers
            ),
        ];

        if (!empty($payload)) {
            $options['json'] = $payload;
        }

        $client->request(
            $method,
            $uri,
            $options
        );

        $this->assertResponseStatusCodeSame($expectedStatusCode);
    }

    protected function requestAndAssertError(
        string $method,
        string $url,
        int $expectedStatusCode = Response::HTTP_INTERNAL_SERVER_ERROR
    ): void {
        $this->sendRequest($method, $url, [], [], $expectedStatusCode);
    }

    /**
     * @param array<string, int|string|float|bool|array|null> $payload
     */
    protected function createEntity(
        string $uri,
        array $payload,
        ?string $expectedType = null
    ): string {
        $data = $this->jsonRequest('POST', $uri, $payload);
        $this->assertResponseStatusCodeSame(201);
        if ($expectedType !== null) {
            $this->assertSame($expectedType, (string) $data['@type']);
        }
        return (string) $data['@id'];
    }

    /**
     * @param array<string, int|string|float|bool|array|null> $payload
     * @param array<string, string> $headers
     *
     * @return array<string, int|string|float|bool|array|null>
     */
    protected function jsonRequest(
        string $method,
        string $uri,
        array $payload = [],
        array $headers = []
    ): array {
        $client = self::createClient();

        $defaultHeaders = [
            'Content-Type' => 'application/ld+json',
        ];
        $headers = array_merge($defaultHeaders, $headers);
        $body = count($payload) === 0 ? null : json_encode($payload);

        $response = $client->request($method, $uri, [
            'headers' => $headers,
            'body' => $body,
        ]);

        return $response->toArray();
    }
}
