<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Infrastructure\Collection;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Event\CustomerCreatedEvent;
use App\Core\Customer\Domain\Event\CustomerDeletedEvent;
use App\Core\Customer\Domain\Event\CustomerUpdatedEvent;
use App\Core\Customer\Infrastructure\Collection\CustomerCacheInvalidationRuleCollection;
use App\Core\Customer\Infrastructure\Collection\CustomerCachePolicyCollection;
use App\Tests\Unit\UnitTestCase;

final class CustomerCacheInvalidationRuleCollectionTest extends UnitTestCase
{
    private CustomerCacheInvalidationRuleCollection $rules;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rules = new CustomerCacheInvalidationRuleCollection();
    }

    public function testRulesExposeEveryDomainEventRule(): void
    {
        self::assertCount(12, $this->rules->rules());

        $this->assertContainsRule(
            CustomerCreatedEvent::class,
            'domain_event',
            CustomerCacheInvalidationRuleCollection::OPERATION_CREATED,
            $this->customerFamilies(),
            CustomerCachePolicyCollection::REFRESH_SOURCE_REPOSITORY
        );
        $this->assertContainsRule(
            CustomerUpdatedEvent::class,
            'domain_event',
            CustomerCacheInvalidationRuleCollection::OPERATION_UPDATED,
            $this->customerFamilies(),
            CustomerCachePolicyCollection::REFRESH_SOURCE_REPOSITORY
        );
        $this->assertContainsRule(
            CustomerDeletedEvent::class,
            'domain_event',
            CustomerCacheInvalidationRuleCollection::OPERATION_DELETED,
            $this->customerFamilies(),
            CustomerCachePolicyCollection::REFRESH_SOURCE_INVALIDATE_ONLY
        );
    }

    public function testRulesExposeCustomerOdmChangeRules(): void
    {
        $this->assertContainsRule(
            Customer::class,
            'odm_change_set',
            CustomerCacheInvalidationRuleCollection::OPERATION_CREATED,
            $this->customerFamilies(),
            CustomerCachePolicyCollection::REFRESH_SOURCE_REPOSITORY
        );
        $this->assertContainsRule(
            Customer::class,
            'odm_change_set',
            CustomerCacheInvalidationRuleCollection::OPERATION_UPDATED,
            $this->customerFamilies(),
            CustomerCachePolicyCollection::REFRESH_SOURCE_REPOSITORY
        );
        $this->assertContainsRule(
            Customer::class,
            'odm_change_set',
            CustomerCacheInvalidationRuleCollection::OPERATION_DELETED,
            $this->customerFamilies(),
            CustomerCachePolicyCollection::REFRESH_SOURCE_INVALIDATE_ONLY
        );
    }

    public function testRulesExposeReferenceOdmChangeRules(): void
    {
        $this->assertContainsRule(
            CustomerStatus::class,
            'odm_change_set',
            CustomerCacheInvalidationRuleCollection::OPERATION_CREATED,
            $this->referenceFamilies(),
            CustomerCachePolicyCollection::REFRESH_SOURCE_INVALIDATE_ONLY
        );
        $this->assertContainsRule(
            CustomerType::class,
            'odm_change_set',
            CustomerCacheInvalidationRuleCollection::OPERATION_DELETED,
            $this->referenceFamilies(),
            CustomerCachePolicyCollection::REFRESH_SOURCE_INVALIDATE_ONLY
        );
    }

    public function testFindsDomainEventRules(): void
    {
        $rules = $this->rules->forDomainEvent(CustomerUpdatedEvent::class);

        self::assertCount(1, $rules);
        self::assertSame(CustomerCachePolicyCollection::CONTEXT, $rules[0]['context']);
        self::assertSame('domain_event', $rules[0]['source']);
        self::assertSame(CustomerUpdatedEvent::class, $rules[0]['subject']);
        self::assertSame(
            CustomerCacheInvalidationRuleCollection::OPERATION_UPDATED,
            $rules[0]['operation']
        );
        self::assertSame(
            CustomerCachePolicyCollection::REFRESH_SOURCE_REPOSITORY,
            $rules[0]['refresh_source']
        );
    }

    public function testFindsReferenceDocumentChangeRules(): void
    {
        $rules = $this->rules->forDocumentChange(
            CustomerType::class,
            CustomerCacheInvalidationRuleCollection::OPERATION_UPDATED
        );

        self::assertCount(1, $rules);
        self::assertSame('odm_change_set', $rules[0]['source']);
        self::assertSame(CustomerType::class, $rules[0]['subject']);
        self::assertSame([
            CustomerCachePolicyCollection::FAMILY_COLLECTION,
            CustomerCachePolicyCollection::FAMILY_REFERENCE,
        ], $rules[0]['families']);
        self::assertSame(
            CustomerCachePolicyCollection::REFRESH_SOURCE_INVALIDATE_ONLY,
            $rules[0]['refresh_source']
        );
    }

    /**
     * @param class-string $subject
     * @param list<string> $families
     */
    private function assertContainsRule(
        string $subject,
        string $source,
        string $operation,
        array $families,
        string $refreshSource
    ): void {
        self::assertContains([
            'context' => CustomerCachePolicyCollection::CONTEXT,
            'source' => $source,
            'subject' => $subject,
            'operation' => $operation,
            'families' => $families,
            'refresh_source' => $refreshSource,
        ], $this->rules->rules());
    }

    /**
     * @return list<string>
     */
    private function customerFamilies(): array
    {
        return [
            CustomerCachePolicyCollection::FAMILY_DETAIL,
            CustomerCachePolicyCollection::FAMILY_LOOKUP,
            CustomerCachePolicyCollection::FAMILY_COLLECTION,
        ];
    }

    /**
     * @return list<string>
     */
    private function referenceFamilies(): array
    {
        return [
            CustomerCachePolicyCollection::FAMILY_COLLECTION,
            CustomerCachePolicyCollection::FAMILY_REFERENCE,
        ];
    }
}
