<?php

declare(strict_types=1);

namespace App\Core\Customer\Infrastructure\Collection;

use App\Shared\Application\DTO\CacheRefreshPolicy;
use InvalidArgumentException;

final readonly class CustomerCachePolicyCollection
{
    public const CONTEXT = 'customer';
    public const FAMILY_DETAIL = 'detail';
    public const FAMILY_LOOKUP = 'lookup';
    public const FAMILY_COLLECTION = 'collection';
    public const FAMILY_REFERENCE = 'reference';
    public const FAMILY_NEGATIVE_LOOKUP = 'negative_lookup';

    public const REFRESH_SOURCE_REPOSITORY = CacheRefreshPolicy::SOURCE_REPOSITORY_REFRESH;
    public const REFRESH_SOURCE_INVALIDATE_ONLY = CacheRefreshPolicy::SOURCE_INVALIDATE_ONLY;

    public function __construct(
        private int $detailTtl = 600,
        private int $lookupTtl = 300,
        private int $collectionTtl = 300,
        private int $referenceTtl = 1800,
        private int $negativeLookupTtl = 60
    ) {
    }

    /**
     * Detail reads are hot and user-facing, so they keep a medium TTL with
     * probabilistic refresh to avoid synchronous recomputation spikes.
     *
     * @return array{
     *     context: string,
     *     family: string,
     *     ttl: int,
     *     beta: float|null,
     *     consistency: string,
     *     refresh_source: string,
     *     tags: list<string>
     * }
     */
    public function detail(): array
    {
        return [
            'context' => self::CONTEXT,
            'family' => self::FAMILY_DETAIL,
            'ttl' => $this->detailTtl,
            'beta' => 1.0,
            'consistency' => 'stale_while_revalidate',
            'refresh_source' => self::REFRESH_SOURCE_REPOSITORY,
            'tags' => ['customer'],
        ];
    }

    /**
     * Email lookups change with customer edits and must stay short-lived while
     * allowing asynchronous repository refresh to repopulate positive hits.
     *
     * @return array{
     *     context: string,
     *     family: string,
     *     ttl: int,
     *     beta: float|null,
     *     consistency: string,
     *     refresh_source: string,
     *     tags: list<string>
     * }
     */
    public function lookup(): array
    {
        return [
            'context' => self::CONTEXT,
            'family' => self::FAMILY_LOOKUP,
            'ttl' => $this->lookupTtl,
            'beta' => null,
            'consistency' => 'eventual',
            'refresh_source' => self::REFRESH_SOURCE_REPOSITORY,
            'tags' => ['customer', 'customer.email'],
        ];
    }

    /**
     * @return array{
     *     context: string,
     *     family: string,
     *     ttl: int,
     *     beta: float|null,
     *     consistency: string,
     *     refresh_source: string,
     *     tags: list<string>
     * }
     */
    public function forFamily(string $family): array
    {
        return match ($family) {
            self::FAMILY_DETAIL => $this->detail(),
            self::FAMILY_LOOKUP => $this->lookup(),
            self::FAMILY_COLLECTION => $this->collection(),
            self::FAMILY_REFERENCE => $this->reference(),
            self::FAMILY_NEGATIVE_LOOKUP => $this->negativeLookup(),
            default => throw new InvalidArgumentException(sprintf(
                'Unsupported customer cache family "%s". Supported families: %s.',
                $family,
                implode(', ', [
                    self::FAMILY_DETAIL,
                    self::FAMILY_LOOKUP,
                    self::FAMILY_COLLECTION,
                    self::FAMILY_REFERENCE,
                    self::FAMILY_NEGATIVE_LOOKUP,
                ])
            )),
        };
    }

    /**
     * @param array{ttl: int} $policy
     */
    public function ttl(array $policy): int
    {
        return $policy['ttl'];
    }

    /**
     * @param array{beta: float|null} $policy
     */
    public function beta(array $policy): float
    {
        return $policy['beta'] ?? 0.0;
    }

    /**
     * @param array{tags: list<string>} $policy
     *
     * @return list<string>
     */
    public function tags(array $policy, string ...$extraTags): array
    {
        return array_values(array_unique([
            ...$policy['tags'],
            ...$extraTags,
        ]));
    }

    /**
     * Collections are invalidated instead of refreshed because list shape
     * depends on caller filters and should be recomputed by the next read.
     *
     * @return array{
     *     context: string,
     *     family: string,
     *     ttl: int,
     *     beta: float|null,
     *     consistency: string,
     *     refresh_source: string,
     *     tags: list<string>
     * }
     */
    private function collection(): array
    {
        return [
            'context' => self::CONTEXT,
            'family' => self::FAMILY_COLLECTION,
            'ttl' => $this->collectionTtl,
            'beta' => null,
            'consistency' => 'invalidate_only',
            'refresh_source' => self::REFRESH_SOURCE_INVALIDATE_ONLY,
            'tags' => ['customer.collection'],
        ];
    }

    /**
     * Reference data changes rarely, so it uses the longest TTL and broad
     * invalidation when related reference documents are changed.
     *
     * @return array{
     *     context: string,
     *     family: string,
     *     ttl: int,
     *     beta: float|null,
     *     consistency: string,
     *     refresh_source: string,
     *     tags: list<string>
     * }
     */
    private function reference(): array
    {
        return [
            'context' => self::CONTEXT,
            'family' => self::FAMILY_REFERENCE,
            'ttl' => $this->referenceTtl,
            'beta' => null,
            'consistency' => 'invalidate_only',
            'refresh_source' => self::REFRESH_SOURCE_INVALIDATE_ONLY,
            'tags' => ['customer.reference'],
        ];
    }

    /**
     * Negative email lookups are intentionally short so newly created customers
     * become discoverable quickly after a prior miss.
     *
     * @return array{
     *     context: string,
     *     family: string,
     *     ttl: int,
     *     beta: float|null,
     *     consistency: string,
     *     refresh_source: string,
     *     tags: list<string>
     * }
     */
    private function negativeLookup(): array
    {
        return [
            'context' => self::CONTEXT,
            'family' => self::FAMILY_NEGATIVE_LOOKUP,
            'ttl' => $this->negativeLookupTtl,
            'beta' => null,
            'consistency' => 'eventual',
            'refresh_source' => self::REFRESH_SOURCE_INVALIDATE_ONLY,
            'tags' => ['customer', 'customer.email'],
        ];
    }
}
