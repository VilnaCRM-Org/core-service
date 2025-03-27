<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Unit\UlidProvider;
use Faker\Factory;
use Faker\Generator;

abstract class BaseIntegrationTest extends ApiTestCase
{
    protected Generator $faker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->faker = Factory::create();
        $this->faker->addProvider(new UlidProvider($this->faker));
    }

    /**
     * @param array  $payload Request body (if any)
     * @param array  $headers Optional additional headers
     *
     * @return array Response data decoded from JSON
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

    /**
     * @param array<string, string> $payload
     * @param array<string, string> $responseData
     */
    protected function assertCreatedResponse(
        array $payload,
        array $responseData
    ): void {
        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame(
            'content-type',
            'application/ld+json; charset=utf-8'
        );
        $this->assertArrayHasKey('@id', $responseData);
        if (isset($payload['email'])) {
            $this->assertSame($payload['email'], $responseData['email']);
        }
    }

    /**
     * Create an entity by sending a POST request.
     *
     * @param string      $uri          API endpoint to call
     * @param array       $payload      Request payload
     * @param string|null $expectedType Optional expected @type value
     *
     * @return string The created entity’s @id
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
}
