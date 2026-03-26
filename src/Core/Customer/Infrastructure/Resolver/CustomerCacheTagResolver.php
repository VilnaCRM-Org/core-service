<?php

declare(strict_types=1);

namespace App\Core\Customer\Infrastructure\Resolver;

use App\Core\Customer\Domain\Entity\Customer;
use App\Shared\Infrastructure\Cache\CacheKeyBuilder;

final readonly class CustomerCacheTagResolver
{
    public function __construct(
        private CacheKeyBuilder $cacheKeyBuilder
    ) {
    }

    /**
     * @return list<string>
     */
    public function resolveForDeletedCustomer(
        ?Customer $customer,
        ?string $deletedEmail = null,
        ?string $deletedId = null
    ): array {
        $tags = ['customer.collection'];

        if ($customer instanceof Customer) {
            $tags[] = 'customer.' . $customer->getUlid();
            $tags[] = 'customer.email.' . $this->cacheKeyBuilder->hashEmail($customer->getEmail());

            return $tags;
        }

        if ($deletedId !== null) {
            $tags[] = 'customer.' . $deletedId;
        }

        if ($deletedEmail !== null) {
            $tags[] = 'customer.email.' . $this->cacheKeyBuilder->hashEmail($deletedEmail);
        }

        return array_values(array_unique($tags));
    }
}
