<?php

declare(strict_types=1);

namespace App\Tests\Integration\Internal\HealthCheck\Application\EventSub;

use App\Internal\HealthCheck\Application\EventSub\CacheCheckSubscriber;
use App\Internal\HealthCheck\Domain\Event\HealthCheckEvent;
use App\Tests\Integration\BaseTest;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\Cache\CacheInterface;

final class CacheCheckSubscriberTest extends BaseTest
{
    private CacheCheckSubscriber $subscriber;
    private CacheInterface $cache;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->cache = new ArrayAdapter();
        $this->subscriber = new CacheCheckSubscriber($this->cache);
    }

    public function testInitialHealthCheckSetsCache(): void
    {
        $event = new HealthCheckEvent();
        $this->subscriber->onHealthCheck($event);
        $cacheItem = $this->cache->getItem('health_check');
        $this->assertTrue(
            $cacheItem->isHit(),
            'Expected cache item present.'
        );
        $this->assertEquals(
            'ok',
            $cacheItem->get(),
            'Cache should have "ok".'
        );
        $this->assertEquals(
            'ok',
            $this->getHealthCheckCacheValue(),
            'Cached value remains ok.'
        );
    }

    public function testHealthCheckCacheIdempotence(): void
    {
        $event = new HealthCheckEvent();
        $this->subscriber->onHealthCheck($event);
        $val = $this->getHealthCheckCacheValue();
        $this->subscriber->onHealthCheck($event);
        $this->assertEquals(
            $val,
            $this->getHealthCheckCacheValue(),
            'Subsequent calls return same value.'
        );
    }

    public function testGetSubscribedEvents(): void
    {
        $exp = [HealthCheckEvent::class => 'onHealthCheck'];
        $this->assertEquals(
            $exp,
            CacheCheckSubscriber::getSubscribedEvents(),
            'Events array should bind HealthCheckEvent to onHealthCheck.'
        );
    }

    private function getHealthCheckCacheValue(): string
    {
        return $this->cache->get(
            'health_check',
            static fn (): string => 'not_ok'
        );
    }
}
