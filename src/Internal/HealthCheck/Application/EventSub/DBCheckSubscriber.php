<?php

declare(strict_types=1);

namespace App\Internal\HealthCheck\Application\EventSub;

use App\Internal\HealthCheck\Domain\Event\HealthCheckEvent;
use Doctrine\ODM\MongoDB\DocumentManager;

final class DBCheckSubscriber extends BaseHealthCheckSubscriber
{
    private DocumentManager $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    public function onHealthCheck(HealthCheckEvent $event): void
    {
        $client = $this->documentManager->getClient();
        // Force cursor iteration so the health check performs an actual round-trip.
        iterator_to_array($client->listDatabases());
    }
}
