<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Internal\HealthCheck\Application\EventSub\DBCheckSubscriber;
use Aws\Sqs\SqsClient;
use Symfony\Component\Cache\Exception\CacheException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Service\ResetInterface;

final class HealthCheckTest extends BaseTest
{
    #[\Override]
    protected function tearDown(): void
    {
        self::ensureKernelShutdown();
        parent::tearDown();
    }

    public function testNormalHealthCheck(): void
    {
        $client = self::createClient();
        $response = $client->request('GET', '/api/health');
        $this->assertEquals(
            Response::HTTP_NO_CONTENT,
            $response->getStatusCode()
        );
    }

    public function testHealthCheckWithCacheFailure(): void
    {
        self::ensureKernelShutdown();
        $client = self::createClient();
        $testContainer = $client->getContainer()->get('test.service_container');

        $cacheMock = $this->createCacheMock();
        $testContainer->set(CacheInterface::class, $cacheMock);

        $this->assertFailedHealthCheck(
            $client,
            Response::HTTP_INTERNAL_SERVER_ERROR,
            'Cache is not working'
        );
    }

    public function testHealthCheckWithBrokerFailure(): void
    {
        self::ensureKernelShutdown();
        $client = self::createClient();
        $testContainer = $client->getContainer()->get('test.service_container');

        $sqsClientMock = $this->createSqsClientMock();
        $testContainer->set(SqsClient::class, $sqsClientMock);

        $this->assertFailedHealthCheck(
            $client,
            Response::HTTP_INTERNAL_SERVER_ERROR,
            'Message broker is not available'
        );
    }

    public function testHealthCheckWithDatabaseFailure(): void
    {
        self::ensureKernelShutdown();
        $client = self::createClient();
        $testContainer = $client->getContainer()->get('test.service_container');

        $dbSubscriberMock = $this->getMockBuilder(
            DBCheckSubscriber::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $dbSubscriberMock->method('onHealthCheck')
            ->willThrowException(new \Exception('Database error'));

        $testContainer->set(DBCheckSubscriber::class, $dbSubscriberMock);

        $response = $client->request('GET', '/api/health');
        $content = $response->getContent(false);
        $this->assertEquals(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $response->getStatusCode()
        );
        $this->assertStringContainsString('Database error', $content);
    }

    private function assertFailedHealthCheck(
        Client $client,
        int $expectedStatusCode,
        string $expectedErrorMessage
    ): void {
        $response = $client->request('GET', '/api/health');
        $content = $response->getContent(false);
        $this->assertEquals($expectedStatusCode, $response->getStatusCode());
        $this->assertStringContainsString($expectedErrorMessage, $content);
    }

    private function createCacheMock(): CacheInterface
    {
        return new class implements CacheInterface, ResetInterface {
            public function get(string $key, callable $callback, float $beta = null, array &$metadata = null): mixed
            {
                throw new CacheException('Cache is not working');
            }

            public function delete(string $key): bool
            {
                return true;
            }

            public function reset(): void
            {
                // No-op for test
            }
        };
    }

    private function createSqsClientMock(): SqsClient
    {
        return new class extends SqsClient {
            private bool $called = false;

            public function __construct()
            {
                // Don't call parent constructor to avoid AWS SDK configuration
            }

            public function __call($name, $args)
            {
                if ($name === 'createQueue' && !$this->called) {
                    $this->called = true;
                    throw new \Exception('Message broker is not available');
                }
                // Don't call parent::__call as it would require AWS SDK setup
                return null;
            }

            public function reset(): void
            {
                // No-op for test
            }
        };
    }
}
