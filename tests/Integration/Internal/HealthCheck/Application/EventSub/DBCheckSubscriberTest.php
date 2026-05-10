<?php

declare(strict_types=1);

namespace App\Tests\Integration\Internal\HealthCheck\Application\EventSub;

use App\Internal\HealthCheck\Application\EventSub\DBCheckSubscriber;
use App\Internal\HealthCheck\Domain\Event\HealthCheckEvent;
use App\Tests\Integration\BaseApiCase;
use Doctrine\ODM\MongoDB\DocumentManager;
use ReflectionClass;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final class DBCheckSubscriberTest extends BaseApiCase
{
    private DocumentManager $documentManager;
    private DBCheckSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->documentManager = $this->container
            ->get('doctrine_mongodb.odm.default_document_manager');
        $this->subscriber = new DBCheckSubscriber($this->documentManager);
    }

    public function testOnHealthCheck(): void
    {
        try {
            $client = $this->documentManager->getClient();
            $databases = $client->listDatabases();
            $this->assertNotEmpty($databases);
        } catch (\Exception $e) {
            $this->fail('MongoDB connection failed: ' . $e->getMessage());
        }

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
