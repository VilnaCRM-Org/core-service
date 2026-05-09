<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Resolver;

use App\Shared\Application\DTO\CacheRefreshPolicy;
use App\Shared\Application\Exception\UnsupportedCacheRefreshPolicyException;
use App\Shared\Application\Resolver\CacheRefreshPolicyResolverInterface;
use RuntimeException;

/**
 * @psalm-suppress UnusedClass Wired through CacheRefreshPolicyResolverInterface.
 */
final readonly class CacheRefreshPolicyResolver implements CacheRefreshPolicyResolverInterface
{
    /**
     * @param iterable<CacheRefreshPolicyResolverInterface> $resolvers
     */
    public function __construct(
        private iterable $resolvers
    ) {
    }

    public function resolve(string $context, string $family): CacheRefreshPolicy
    {
        foreach ($this->resolvers as $resolver) {
            if ($resolver === $this) {
                continue;
            }

            try {
                return $resolver->resolve($context, $family);
            } catch (UnsupportedCacheRefreshPolicyException) {
                continue;
            }
        }

        throw new RuntimeException(sprintf(
            'No cache refresh policy registered for "%s.%s".',
            $context,
            $family
        ));
    }
}
