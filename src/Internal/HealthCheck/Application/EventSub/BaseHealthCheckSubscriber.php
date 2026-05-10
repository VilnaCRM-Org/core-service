<?php

declare(strict_types=1);

namespace App\Internal\HealthCheck\Application\EventSub;

use App\Internal\HealthCheck\Domain\Event\HealthCheckEvent;

abstract class BaseHealthCheckSubscriber
{
    abstract public function onHealthCheck(HealthCheckEvent $event): void;
}
