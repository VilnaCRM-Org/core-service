<?php

declare(strict_types=1);

namespace App\Tests\Unit\Internal\HealthCheck\Application\EventSub;

use App\Internal\HealthCheck\Application\EventSub\DBCheckSubscriber;
use App\Internal\HealthCheck\Domain\Event\HealthCheckEvent;
use App\Tests\Unit\UnitTestCase;
use Doctrine\ODM\MongoDB\DocumentManager;
use Iterator;
use MongoDB\Client;
use MongoDB\Driver\Exception\ConnectionException;
use ReflectionClass;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

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
        $databaseIterator = $this->createMock(Iterator::class);
        $databaseIterator->expects($this->once())
            ->method('rewind');

        $clientMock->expects($this->once())
            ->method('listDatabases')
            ->willReturn($databaseIterator);

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

    public function testRegistersHealthCheckListenerAttribute(): void
    {
        $listener = (new ReflectionClass(DBCheckSubscriber::class))
            ->getAttributes(AsEventListener::class)[0]
            ->newInstance();

        $this->assertSame(HealthCheckEvent::class, $listener->event);
        $this->assertSame('onHealthCheck', $listener->method);
    }
}
