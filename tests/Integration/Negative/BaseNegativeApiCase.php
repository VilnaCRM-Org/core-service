<?php

declare(strict_types=1);

namespace App\Tests\Integration\Negative;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Integration\Negative\Kernel\NegativeKernel;
use App\Tests\Unit\UlidProvider;
use Faker\Factory;
use Symfony\Component\HttpFoundation\Response;

abstract class BaseNegativeApiCase extends ApiTestCase
{
    /**
     * @var array{server?: string|null, env?: string|null}
     */
    private array $previousKernelClass = [];

    protected function setUp(): void
    {
        $this->previousKernelClass = [
            'server' => $_SERVER['KERNEL_CLASS'] ?? null,
            'env' => $_ENV['KERNEL_CLASS'] ?? null,
        ];
        $_SERVER['KERNEL_CLASS'] = NegativeKernel::class;
        $_ENV['KERNEL_CLASS'] = NegativeKernel::class;

        parent::setUp();
        $this->container = $this->getContainer();
        $this->faker = Factory::create();
        $this->faker->addProvider(new UlidProvider($this->faker));
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->restoreKernelClass();
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

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    protected function requestAndAssertError(
        string $method,
        string $url,
        int $expectedStatusCode = Response::HTTP_INTERNAL_SERVER_ERROR
    ): void {
        $this->sendRequest($method, $url, [], [], $expectedStatusCode);
    }

    private function restoreKernelClass(): void
    {
        if (array_key_exists('server', $this->previousKernelClass) && $this->previousKernelClass['server'] !== null) {
            $_SERVER['KERNEL_CLASS'] = $this->previousKernelClass['server'];
        } else {
            unset($_SERVER['KERNEL_CLASS']);
        }

        if (array_key_exists('env', $this->previousKernelClass) && $this->previousKernelClass['env'] !== null) {
            $_ENV['KERNEL_CLASS'] = $this->previousKernelClass['env'];
        } else {
            unset($_ENV['KERNEL_CLASS']);
        }
    }
}
