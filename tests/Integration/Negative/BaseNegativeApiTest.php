<?php

declare(strict_types=1);

namespace App\Tests\Integration\Negative;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Integration\Negative\Kernel\NegativeKernel;
use App\Tests\Unit\UlidProvider;
use Faker\Factory;
use Symfony\Component\HttpFoundation\Response;

abstract class BaseNegativeApiTest extends ApiTestCase
{
    #[\Override]
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

    /**
     * @param array<string, string> $payload
     * @param array<string, string> $headers
     */
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
                ['Content-Type' => $contentType],
                $headers
            ),
        ];

        if (count($payload) > 0) {
            $options['json'] = $payload;
        }

        $client->request($method, $uri, $options);

        $this->assertResponseStatusCodeSame($expectedStatusCode);
    }

    protected function requestAndAssertError(
        string $method,
        string $url,
        int $expectedStatusCode = Response::HTTP_INTERNAL_SERVER_ERROR
    ): void {
        $this->sendRequest($method, $url, [], [], $expectedStatusCode);
    }
}
