<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Cache;

/**
 * Cache Key Builder Service
 *
 * Responsibilities:
 * - Centralized cache key generation
 * - Consistent email hashing strategy
 * - Eliminates duplication across repository and handlers
 *
 * Usage:
 * - buildCustomerKey($customerId) → "customer.{id}"
 * - buildCustomerEmailKey($email) → "customer.email.{hash}"
 */
final readonly class CacheKeyBuilder
{
    /**
     * Build cache key from namespace and parts
     */
    public function build(string $namespace, string ...$parts): string
    {
        return $namespace . '.' . implode('.', $parts);
    }

    /**
     * Build customer cache key by ID
     */
    public function buildCustomerKey(string $customerId): string
    {
        return $this->build('customer', $customerId);
    }

    /**
     * Build customer email cache key with hash
     */
    public function buildCustomerEmailKey(string $email): string
    {
        $emailHash = $this->hashEmail($email);

        return $this->build('customer', 'email', $emailHash);
    }

    /**
     * Build cache key for customer collections (filters normalized + hashed)
     *
     * @param array<string, string|int|float|bool|array|null> $filters
     */
    public function buildCustomerCollectionKey(array $filters): string
    {
        ksort($filters);

        return $this->build(
            'customer',
            'collection',
            hash('sha256', json_encode($filters, \JSON_THROW_ON_ERROR))
        );
    }

    /**
     * Hash email consistently (lowercase + SHA256)
     *
     * Strategy:
     * - Lowercase normalization (email case-insensitive)
     * - SHA256 hashing (fixed length, secure)
     * - Prevents cache key length issues
     */
    public function hashEmail(string $email): string
    {
        return hash('sha256', strtolower($email));
    }
}
