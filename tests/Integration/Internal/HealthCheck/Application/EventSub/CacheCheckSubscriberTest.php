<?php

declare(strict_types=1);

namespace App\Tests\Integration\Internal\HealthCheck\Application\EventSub;

use App\Internal\HealthCheck\Application\EventSub\CacheCheckSubscriber;
use App\Internal\HealthCheck\Domain\Event\HealthCheckEvent;
use App\Tests\Integration\BaseIntegrationTest;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\Cache\CacheInterface;

final class CacheCheckSubscriberTest extends BaseIntegrationTest
{
    private CacheCheckSubscriber $subscriber;
    private CacheInterface $cache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cache = new ArrayAdapter();
        $this->subscriber = new CacheCheckSubscriber($this->cache);
    }

    public function testOnHealthCheckCachesResult(): void
    {
        $event = new HealthCheckEvent();

        $this->subscriber->onHealthCheck($event);

        $cacheItem = $this->cache->getItem('health_check');
        $this->assertTrue($cacheItem->isHit(), 'Expected the cache item to be present.');
        $this->assertEquals(
            'ok',
            $cacheItem->get(),
            'The cache should contain "ok" as the value for the health_check key.'
        );

        $cachedValue = $this->cache->get('health_check', static function () {
            return 'not_ok';
        });
        $this->assertEquals(
            'ok',
            $cachedValue,
            'The cached value should remain "ok",
            and the fallback should not be invoked.'
        );

        $this->subscriber->onHealthCheck($event);
        $cachedValueAgain = $this->cache->get(
            'health_check',
            static function () {
                return 'not_ok';
            }
        );
        $this->assertEquals(
            $cachedValue,
            $cachedValueAgain,
            'Subsequent calls should continue to return the same cached value.'
        );
    }

    public function testGetSubscribedEvents(): void
    {
        $expected = [HealthCheckEvent::class => 'onHealthCheck'];
        $this->assertEquals(
            $expected,
            CacheCheckSubscriber::getSubscribedEvents(),
            'The events array should correctly bind the HealthCheckEvent to the onHealthCheck method.'
        );
    }
}
