<?php

declare(strict_types=1);

namespace App\Core\Customer\Infrastructure\Resolver;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Infrastructure\Collection\CustomerCacheTagCollection;
use App\Shared\Infrastructure\Cache\CacheKeyBuilder;

final readonly class CustomerCacheTagResolver
{
    public function __construct(
        private CacheKeyBuilder $cacheKeyBuilder
    ) {
    }

    public function resolveForDeletedCustomer(
        ?Customer $customer,
        ?string $deletedEmail = null,
        ?string $deletedId = null
    ): CustomerCacheTagCollection {
        $tags = new CustomerCacheTagCollection('customer.collection');

        if ($customer instanceof Customer) {
            return $tags->with(
                'customer.' . $customer->getUlid(),
                'customer.email.' . $this->cacheKeyBuilder->hashEmail($customer->getEmail())
            );
        }

        if ($deletedId !== null) {
            $tags = $tags->with('customer.' . $deletedId);
        }

        if ($deletedEmail !== null) {
            $tags = $tags->with(
                'customer.email.' . $this->cacheKeyBuilder->hashEmail($deletedEmail)
            );
        }

        return $tags;
    }
}
