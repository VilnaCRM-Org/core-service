<?php

declare(strict_types=1);

namespace App\Tests\Unit\Internal\HealthCheck\Application\EventSub;

use App\Internal\HealthCheck\Application\EventSub\DBCheckSubscriber;
use App\Internal\HealthCheck\Domain\Event\HealthCheckEvent;
use App\Tests\Unit\UnitTestCase;
use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\Client;
use MongoDB\Driver\Exception\ConnectionException;

final class DBCheckSubscriberTest extends UnitTestCase
{
    private DocumentManager $documentManager;
    private DBCheckSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->documentManager = $this->createMock(DocumentManager::class);

        $this->subscriber = new DBCheckSubscriber($this->documentManager);
    }

    public function testOnHealthCheckSuccess(): void
    {
        $clientMock = $this->createMock(Client::class);
        $clientMock->expects($this->once())
            ->method('listDatabases')
            ->willReturn([]);

        $this->documentManager->expects($this->once())
            ->method('getClient')
            ->willReturn($clientMock);

        $event = new HealthCheckEvent();
        $this->subscriber->onHealthCheck($event);
    }

    public function testOnHealthCheckFailure(): void
    {
        $clientMock = $this->createMock(Client::class);

        $clientMock->expects($this->once())
            ->method('listDatabases')
            ->willThrowException(
                new ConnectionException('Unable to connect to MongoDB')
            );

        $this->documentManager->expects($this->once())
            ->method('getClient')
            ->willReturn($clientMock);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to connect to MongoDB');

        $event = new HealthCheckEvent();
        $this->subscriber->onHealthCheck($event);
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertSame(
            [HealthCheckEvent::class => 'onHealthCheck'],
            DBCheckSubscriber::getSubscribedEvents()
        );
    }
}
