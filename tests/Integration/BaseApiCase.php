<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Tests\Unit\UlidProvider;
use Doctrine\Common\DataFixtures\Purger\MongoDBPurger;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class BaseApiCase extends ApiTestCase
{
    protected Generator $faker;

    protected ContainerInterface $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = $this->getContainer();
        $this->faker = Factory::create();
        $this->faker->addProvider(new UlidProvider($this->faker));
        $dm = $this->container->get('doctrine_mongodb.odm.document_manager');
        $purger = new MongoDBPurger($dm);
        $purger->purge();
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
        return $this->jsonRequestWithClient(
            self::createClient(),
            $method,
            $uri,
            $payload,
            $headers
        );
    }

    /**
     * @param array<string, int|string|float|bool|array|null> $payload
     * @param array<string, string> $headers
     *
     * @return array<string, int|string|float|bool|array|null>
     */
    protected function jsonRequestWithClient(
        Client $client,
        string $method,
        string $uri,
        array $payload = [],
        array $headers = []
    ): array {
        $defaultHeaders = [
            'Content-Type' => 'application/ld+json',
        ];
        $headers = array_merge($defaultHeaders, $headers);
        $body = count($payload) === 0
            ? null
            : json_encode($payload, JSON_THROW_ON_ERROR);

        $response = $client->request($method, $uri, [
            'headers' => $headers,
            'body' => $body,
        ]);

        return $response->toArray();
    }

    protected function createSameKernelClient(): Client
    {
        $client = self::createClient();
        $client->disableReboot();

        return $client;
    }

    /**
     * @param array<string, string> $payload
     * @param array<string, string> $responseData
     *
     * @psalm-suppress PossiblyUnusedMethod
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
     * @param array<string, int|string|float|bool|array|null> $payload
     */
    protected function createEntity(
        string $uri,
        array $payload,
        ?string $expectedType = null
    ): string {
        return $this->createEntityWithClient(
            self::createClient(),
            $uri,
            $payload,
            $expectedType
        );
    }

    /**
     * @param array<string, int|string|float|bool|array|null> $payload
     */
    protected function createEntityWithClient(
        Client $client,
        string $uri,
        array $payload,
        ?string $expectedType = null
    ): string {
        $data = $this->jsonRequestWithClient($client, 'POST', $uri, $payload);
        self::assertSame(201, $client->getResponse()->getStatusCode());
        if ($expectedType !== null) {
            $this->assertSame($expectedType, (string) $data['@type']);
        }
        return (string) $data['@id'];
    }

    /**
     * @return array<string, string|bool>
     */
    protected function getCustomerPayloadWithClient(
        Client $client,
        ?string $initials = null
    ): array {
        return $this->getCustomerPayloadWithRelations(
            $this->createCustomerTypeEntityWithClient($client),
            $this->createCustomerStatusEntityWithClient($client),
            $initials
        );
    }

    /**
     * @return array{value: string}
     */
    protected function getCustomerTypePayload(?string $value = null): array
    {
        return [
            'value' => $value ?? $this->faker->word(),
        ];
    }

    /**
     * @return array{value: string}
     */
    protected function getCustomerStatusPayload(?string $value = null): array
    {
        return [
            'value' => $value ?? $this->faker->word(),
        ];
    }

    protected function createCustomerTypeEntityWithClient(
        Client $client,
        ?string $value = null
    ): string {
        return $this->createLookupEntityWithClient(
            $client,
            '/api/customer_types',
            $value,
            $this->getCustomerTypePayload(...)
        );
    }

    protected function createCustomerStatusEntityWithClient(
        Client $client,
        ?string $value = null
    ): string {
        return $this->createLookupEntityWithClient(
            $client,
            '/api/customer_statuses',
            $value,
            $this->getCustomerStatusPayload(...)
        );
    }

    /**
     * @return array<string, string|bool>
     */
    private function getCustomerPayloadWithRelations(
        string $type,
        string $status,
        ?string $initials = null
    ): array {
        return [
            'initials' => $initials ?? $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'leadSource' => $this->faker->word(),
            'type' => $type,
            'status' => $status,
            'confirmed' => $this->faker->boolean(),
        ];
    }

    /**
     * @param callable(?string): array{value: string} $payloadProvider
     */
    private function createLookupEntityWithClient(
        Client $client,
        string $uri,
        ?string $value,
        callable $payloadProvider
    ): string {
        return $this->createEntityWithClient($client, $uri, $payloadProvider($value));
    }
}
