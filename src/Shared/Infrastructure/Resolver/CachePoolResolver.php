<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Resolver;

use App\Shared\Application\Resolver\CachePoolResolverInterface;
use RuntimeException;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;

final readonly class CachePoolResolver implements CachePoolResolverInterface
{
    public function __construct(
        private ServiceProviderInterface $cachePools
    ) {
    }

    public function resolve(string $context): TagAwareCacheInterface
    {
        if (! $this->cachePools->has($context)) {
            throw new RuntimeException(sprintf(
                'No cache pool registered for context "%s".',
                $context
            ));
        }

        $cachePool = $this->cachePools->get($context);

        if (! $cachePool instanceof TagAwareCacheInterface) {
            throw new RuntimeException(sprintf(
                'Cache pool registered for context "%s" must support tags.',
                $context
            ));
        }

        return $cachePool;
    }
}
