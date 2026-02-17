<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Internal\HealthCheck\Application\EventSub\DBCheckSubscriber;
use Aws\Sqs\SqsClient;
use Symfony\Component\Cache\Exception\CacheException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\CacheInterface;

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
        $cacheMock = $this->createMock(CacheInterface::class);
        $cacheMock->method('get')
            ->willThrowException(new CacheException('Cache is not working'));

        return $cacheMock;
    }

    private function createSqsClientMock(): SqsClient
    {
        $sqsClientMock = $this->getMockBuilder(SqsClient::class)
            ->disableOriginalConstructor()
            ->getMock();
        $sqsClientMock->expects($this->once())
            ->method('createQueue')
            ->willThrowException(new \Exception(
                'Message broker is not available'
            ));

        return $sqsClientMock;
    }
}
