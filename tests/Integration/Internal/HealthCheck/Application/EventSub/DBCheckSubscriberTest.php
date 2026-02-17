<?php

declare(strict_types=1);

namespace App\Tests\Integration\Internal\HealthCheck\Application\EventSub;

use App\Internal\HealthCheck\Application\EventSub\DBCheckSubscriber;
use App\Internal\HealthCheck\Domain\Event\HealthCheckEvent;
use App\Tests\Integration\BaseTest;
use Doctrine\ODM\MongoDB\DocumentManager;

final class DBCheckSubscriberTest extends BaseTest
{
    private DocumentManager $documentManager;
    private DBCheckSubscriber $subscriber;

    #[\Override]
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

    public function testGetSubscribedEvents(): void
    {
        $this->assertSame(
            [HealthCheckEvent::class => 'onHealthCheck'],
            DBCheckSubscriber::getSubscribedEvents()
        );
    }
}
