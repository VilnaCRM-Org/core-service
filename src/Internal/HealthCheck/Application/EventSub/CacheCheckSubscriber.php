<?php

declare(strict_types=1);

namespace App\Internal\HealthCheck\Application\EventSub;

use App\Internal\HealthCheck\Domain\Event\HealthCheckEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Cache\CacheInterface;

#[AsEventListener(event: HealthCheckEvent::class, method: 'onHealthCheck')]
final class CacheCheckSubscriber extends BaseHealthCheckSubscriber
{
    private CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function onHealthCheck(HealthCheckEvent $event): void
    {
        $this->cache->get('health_check', fn () => $this->cacheMissHandler());
    }

    private function cacheMissHandler(): string
    {
        return 'ok';
    }
}
