<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Unit\UlidProvider;
use Doctrine\Common\DataFixtures\Purger\MongoDBPurger;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class BaseIntegrationTest extends ApiTestCase
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
}
