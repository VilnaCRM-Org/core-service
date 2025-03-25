<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Internal\HealthCheck\Application\EventSub\DBCheckSubscriber;
use Aws\Sqs\SqsClient;
use Symfony\Component\Cache\Exception\CacheException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\CacheInterface;

final class HealthCheckIntegrationTest extends ApiTestCase
{
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

        $cacheMock = $this->getMockForAbstractClass(
            CacheInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['reset']
        );
        $cacheMock->method('get')
            ->willThrowException(new CacheException('Cache is not working'));
        $cacheMock->method('reset')->willReturn(null);

        $testContainer->set(CacheInterface::class, $cacheMock);

        $response = $client->request('GET', '/api/health');
        $content = $response->getContent(false);
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertStringContainsString('Cache is not working', $content);
    }

    public function testHealthCheckWithBrokerFailure(): void
    {
        self::ensureKernelShutdown();
        $client = self::createClient();
        $testContainer = $client->getContainer()->get('test.service_container');
        $sqsClientMock = $this->getMockBuilder(SqsClient::class)
            ->disableOriginalConstructor()
            ->addMethods(['createQueue', 'reset'])->getMock();
        $sqsClientMock->expects($this->once())
            ->method('createQueue')
            ->willThrowException(new \Exception(
                'Message broker is not available'
            ));
        $sqsClientMock->method('reset')->willReturn(null);

        $testContainer->set(SqsClient::class, $sqsClientMock);

        $response = $client->request('GET', '/api/health');
        $content = $response->getContent(false);
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertStringContainsString(
            'Message broker is not available',
            $content
        );
    }

    public function testHealthCheckWithDatabaseFailure(): void
    {
        self::ensureKernelShutdown();
        $client = self::createClient();
        $testContainer = $client->getContainer()->get('test.service_container');

        $dbSubscriberMock = $this->getMockBuilder(DBCheckSubscriber::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbSubscriberMock->method('onHealthCheck')
            ->willThrowException(new \Exception('Database error'));

        $testContainer->set(DBCheckSubscriber::class, $dbSubscriberMock);

        $response = $client->request('GET', '/api/health');
        $content = $response->getContent(false);
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertStringContainsString('Database error', $content);
    }
}
