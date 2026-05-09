<?php

declare(strict_types=1);

namespace App\Core\Customer\Infrastructure\Collection;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Event\CustomerCreatedEvent;
use App\Core\Customer\Domain\Event\CustomerDeletedEvent;
use App\Core\Customer\Domain\Event\CustomerUpdatedEvent;
use App\Shared\Application\DTO\CacheInvalidationRule;

final readonly class CustomerCacheInvalidationRuleCollection
{
    public const OPERATION_CREATED = CacheInvalidationRule::OPERATION_CREATED;
    public const OPERATION_UPDATED = CacheInvalidationRule::OPERATION_UPDATED;
    public const OPERATION_DELETED = CacheInvalidationRule::OPERATION_DELETED;

    /**
     * @return list<array{
     *     context: string,
     *     source: string,
     *     subject: class-string,
     *     operation: string,
     *     families: list<string>,
     *     refresh_source: string
     * }>
     */
    public function rules(): array
    {
        return [
            ...$this->domainEventRules(),
            ...$this->customerOdmRules(),
            ...$this->referenceOdmRules(),
        ];
    }

    /**
     * @return list<array{
     *     context: string,
     *     source: string,
     *     subject: class-string,
     *     operation: string,
     *     families: list<string>,
     *     refresh_source: string
     * }>
     */
    public function forDomainEvent(string $eventClass): array
    {
        return array_values(array_filter(
            $this->rules(),
            static fn (array $rule): bool => $rule['source'] === 'domain_event'
                && $rule['subject'] === $eventClass
        ));
    }

    /**
     * @param class-string $documentClass
     *
     * @return list<array{
     *     context: string,
     *     source: string,
     *     subject: class-string,
     *     operation: string,
     *     families: list<string>,
     *     refresh_source: string
     * }>
     */
    public function forDocumentChange(string $documentClass, string $operation): array
    {
        return array_values(array_filter(
            $this->rules(),
            static fn (array $rule): bool => $rule['source'] === 'odm_change_set'
                && $rule['subject'] === $documentClass
                && $rule['operation'] === $operation
        ));
    }

    /**
     * @return list<array{
     *     context: string,
     *     source: string,
     *     subject: class-string,
     *     operation: string,
     *     families: list<string>,
     *     refresh_source: string
     * }>
     */
    private function domainEventRules(): array
    {
        return [
            $this->domainEventRule(
                CustomerCreatedEvent::class,
                self::OPERATION_CREATED,
                CustomerCachePolicyCollection::REFRESH_SOURCE_REPOSITORY
            ),
            $this->domainEventRule(
                CustomerUpdatedEvent::class,
                self::OPERATION_UPDATED,
                CustomerCachePolicyCollection::REFRESH_SOURCE_REPOSITORY
            ),
            $this->domainEventRule(
                CustomerDeletedEvent::class,
                self::OPERATION_DELETED,
                CustomerCachePolicyCollection::REFRESH_SOURCE_INVALIDATE_ONLY
            ),
        ];
    }

    /**
     * @return list<array{
     *     context: string,
     *     source: string,
     *     subject: class-string,
     *     operation: string,
     *     families: list<string>,
     *     refresh_source: string
     * }>
     */
    private function customerOdmRules(): array
    {
        return [
            $this->customerOdmRule(
                self::OPERATION_CREATED,
                CustomerCachePolicyCollection::REFRESH_SOURCE_REPOSITORY
            ),
            $this->customerOdmRule(
                self::OPERATION_UPDATED,
                CustomerCachePolicyCollection::REFRESH_SOURCE_REPOSITORY
            ),
            $this->customerOdmRule(
                self::OPERATION_DELETED,
                CustomerCachePolicyCollection::REFRESH_SOURCE_INVALIDATE_ONLY
            ),
        ];
    }

    /**
     * @return list<array{
     *     context: string,
     *     source: string,
     *     subject: class-string,
     *     operation: string,
     *     families: list<string>,
     *     refresh_source: string
     * }>
     */
    private function referenceOdmRules(): array
    {
        return [
            $this->referenceOdmRule(CustomerStatus::class, self::OPERATION_CREATED),
            $this->referenceOdmRule(CustomerStatus::class, self::OPERATION_UPDATED),
            $this->referenceOdmRule(CustomerStatus::class, self::OPERATION_DELETED),
            $this->referenceOdmRule(CustomerType::class, self::OPERATION_CREATED),
            $this->referenceOdmRule(CustomerType::class, self::OPERATION_UPDATED),
            $this->referenceOdmRule(CustomerType::class, self::OPERATION_DELETED),
        ];
    }

    /**
     * @param class-string $eventClass
     *
     * @return array{
     *     context: string,
     *     source: string,
     *     subject: class-string,
     *     operation: string,
     *     families: list<string>,
     *     refresh_source: string
     * }
     */
    private function domainEventRule(
        string $eventClass,
        string $operation,
        string $refreshSource
    ): array {
        return [
            'context' => CustomerCachePolicyCollection::CONTEXT,
            'source' => 'domain_event',
            'subject' => $eventClass,
            'operation' => $operation,
            'families' => [
                CustomerCachePolicyCollection::FAMILY_DETAIL,
                CustomerCachePolicyCollection::FAMILY_LOOKUP,
                CustomerCachePolicyCollection::FAMILY_COLLECTION,
            ],
            'refresh_source' => $refreshSource,
        ];
    }

    /**
     * @return array{
     *     context: string,
     *     source: string,
     *     subject: class-string,
     *     operation: string,
     *     families: list<string>,
     *     refresh_source: string
     * }
     */
    private function customerOdmRule(string $operation, string $refreshSource): array
    {
        return [
            'context' => CustomerCachePolicyCollection::CONTEXT,
            'source' => 'odm_change_set',
            'subject' => Customer::class,
            'operation' => $operation,
            'families' => [
                CustomerCachePolicyCollection::FAMILY_DETAIL,
                CustomerCachePolicyCollection::FAMILY_LOOKUP,
                CustomerCachePolicyCollection::FAMILY_COLLECTION,
            ],
            'refresh_source' => $refreshSource,
        ];
    }

    /**
     * @param class-string $documentClass
     *
     * @return array{
     *     context: string,
     *     source: string,
     *     subject: class-string,
     *     operation: string,
     *     families: list<string>,
     *     refresh_source: string
     * }
     */
    private function referenceOdmRule(string $documentClass, string $operation): array
    {
        return [
            'context' => CustomerCachePolicyCollection::CONTEXT,
            'source' => 'odm_change_set',
            'subject' => $documentClass,
            'operation' => $operation,
            'families' => [
                CustomerCachePolicyCollection::FAMILY_COLLECTION,
                CustomerCachePolicyCollection::FAMILY_REFERENCE,
            ],
            'refresh_source' => CustomerCachePolicyCollection::REFRESH_SOURCE_INVALIDATE_ONLY,
        ];
    }
}
