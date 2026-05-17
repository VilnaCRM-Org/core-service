<?php

declare(strict_types=1);

namespace App\Tests\Integration\Internal\HealthCheck\Application\EventSub;

use App\Internal\HealthCheck\Application\EventSub\CacheCheckSubscriber;
use App\Internal\HealthCheck\Domain\Event\HealthCheckEvent;
use App\Tests\Integration\BaseApiCase;
use ReflectionClass;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Cache\CacheInterface;

final class CacheCheckSubscriberTest extends BaseApiCase
{
    private CacheCheckSubscriber $subscriber;
    private CacheInterface $cache;

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

    public function testRegistersHealthCheckListenerAttribute(): void
    {
        $listener = (new ReflectionClass(CacheCheckSubscriber::class))
            ->getAttributes(AsEventListener::class)[0]
            ->newInstance();

        $this->assertSame(HealthCheckEvent::class, $listener->event);
        $this->assertSame('onHealthCheck', $listener->method);
    }

    private function getHealthCheckCacheValue(): string
    {
        return $this->cache->get(
            'health_check',
            static fn (): string => 'not_ok'
        );
    }
}
