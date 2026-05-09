<?php

declare(strict_types=1);

namespace App\Core\Customer\Infrastructure\Resolver;

use App\Core\Customer\Infrastructure\Collection\CustomerCachePolicyCollection;
use App\Shared\Application\DTO\CacheRefreshPolicy;
use App\Shared\Application\Exception\UnsupportedCacheRefreshPolicyException;
use App\Shared\Application\Resolver\CacheRefreshPolicyResolverInterface;

final readonly class CustomerCachePolicyResolver implements CacheRefreshPolicyResolverInterface
{
    public function __construct(
        private CustomerCachePolicyCollection $policies
    ) {
    }

    public function resolve(string $context, string $family): CacheRefreshPolicy
    {
        if ($context !== CustomerCachePolicyCollection::CONTEXT) {
            throw UnsupportedCacheRefreshPolicyException::forContext($context);
        }

        $policy = $this->policies->forFamily($family);

        return CacheRefreshPolicy::create(
            $policy['context'],
            $policy['family'],
            $policy['ttl'],
            $policy['beta'],
            $policy['consistency'],
            $policy['refresh_source']
        );
    }
}
