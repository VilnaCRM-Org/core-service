<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Infrastructure\Resolver;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Infrastructure\Collection\CustomerCacheInvalidationRuleCollection;
use App\Core\Customer\Infrastructure\Collection\CustomerCachePolicyCollection;
use App\Core\Customer\Infrastructure\Resolver\CustomerCacheInvalidationTagResolver;
use App\Shared\Application\DTO\CacheChangeSet;
use App\Shared\Application\DTO\CacheFieldChange;
use App\Shared\Application\DTO\CacheInvalidationRule;
use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use App\Tests\Unit\UnitTestCase;
use ReflectionProperty;

final class CustomerCacheInvalidationTagResolverTest extends UnitTestCase
{
    private CustomerCacheInvalidationTagResolver $resolver;
    private CacheKeyBuilder $cacheKeyBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheKeyBuilder = new CacheKeyBuilder();
        $this->resolver = new CustomerCacheInvalidationTagResolver(
            $this->cacheKeyBuilder,
            new CustomerCacheInvalidationRuleCollection()
        );
    }

    public function testSupportsCustomerAndReferenceDocumentsForConfiguredOperations(): void
    {
        self::assertTrue($this->resolver->supports(
            $this->customer('current@example.com'),
            CacheInvalidationRule::OPERATION_UPDATED
        ));
        self::assertTrue($this->resolver->supports(
            new CustomerType('business', new Ulid((string) $this->faker->ulid())),
            CacheInvalidationRule::OPERATION_UPDATED
        ));
        self::assertTrue($this->resolver->supports(
            new CustomerStatus('active', new Ulid((string) $this->faker->ulid())),
            CacheInvalidationRule::OPERATION_DELETED
        ));
        self::assertFalse($this->resolver->supports(
            new \stdClass(),
            CacheInvalidationRule::OPERATION_UPDATED
        ));
        self::assertFalse($this->resolver->supports(
            $this->customer('current@example.com'),
            'unknown'
        ));
    }

    public function testContextReturnsCustomerCacheContext(): void
    {
        self::assertSame(
            CustomerCachePolicyCollection::CONTEXT,
            $this->resolver->context(
                $this->customer('current@example.com'),
                CacheInvalidationRule::OPERATION_UPDATED
            )
        );
    }

    public function testResolveTagsIncludesCurrentAndPreviousEmailTags(): void
    {
        $customer = $this->customer('new@example.com');
        $changeSet = CacheChangeSet::create(
            CacheFieldChange::create('email', 'old@example.com', 'new@example.com')
        );

        $tags = iterator_to_array($this->resolver->resolveTags(
            $customer,
            CacheInvalidationRule::OPERATION_UPDATED,
            $changeSet
        ));

        self::assertSame([
            'customer.' . $customer->getUlid(),
            'customer.email.' . $this->cacheKeyBuilder->hashEmail('new@example.com'),
            'customer.email.' . $this->cacheKeyBuilder->hashEmail('old@example.com'),
            'customer.collection',
        ], $tags);
    }

    public function testResolveForChangeSetBuildsTagsFromIdentifierArrays(): void
    {
        $tags = iterator_to_array($this->resolver->resolveForChangeSet(
            [
                'customer_id' => 'customer-1',
                'email' => 'current@example.com',
            ],
            [
                'email' => ['previous@example.com', 'current@example.com'],
            ]
        ));

        self::assertSame([
            'customer.customer-1',
            'customer.email.' . $this->cacheKeyBuilder->hashEmail('current@example.com'),
            'customer.email.' . $this->cacheKeyBuilder->hashEmail('previous@example.com'),
            'customer.collection',
        ], $tags);
    }

    public function testResolveRefreshCommandsUsesInvalidateOnlyForDeletes(): void
    {
        $customer = $this->customer('delete@example.com');

        $commands = iterator_to_array($this->resolver->resolveRefreshCommands(
            $customer,
            CacheInvalidationRule::OPERATION_DELETED,
            CacheChangeSet::empty()
        ));

        self::assertCount(2, $commands);
        self::assertSame(CustomerCachePolicyCollection::FAMILY_DETAIL, $commands[0]->family());
        self::assertSame(CustomerCachePolicyCollection::FAMILY_LOOKUP, $commands[1]->family());
        self::assertSame(
            CustomerCachePolicyCollection::REFRESH_SOURCE_INVALIDATE_ONLY,
            $commands[0]->refreshSource()
        );
        self::assertSame(
            CustomerCachePolicyCollection::REFRESH_SOURCE_INVALIDATE_ONLY,
            $commands[1]->refreshSource()
        );
    }

    public function testResolveTagsForReferenceDocumentsInvalidateBroadCustomerTags(): void
    {
        $referenceDocuments = [
            new CustomerType('business', new Ulid((string) $this->faker->ulid())),
            new CustomerStatus('active', new Ulid((string) $this->faker->ulid())),
        ];

        foreach ($referenceDocuments as $referenceDocument) {
            $tags = iterator_to_array($this->resolver->resolveTags(
                $referenceDocument,
                CacheInvalidationRule::OPERATION_UPDATED,
                CacheChangeSet::empty()
            ));

            self::assertSame([
                'customer',
                'customer.collection',
                'customer.reference',
            ], $tags);
            self::assertSame([], iterator_to_array($this->resolver->resolveRefreshCommands(
                $referenceDocument,
                CacheInvalidationRule::OPERATION_UPDATED,
                CacheChangeSet::empty()
            )));
        }
    }

    public function testResolveRefreshCommandsUsesCurrentEmailFromChangeSet(): void
    {
        $customer = $this->customer('persisted@example.com');
        $changeSet = CacheChangeSet::create(
            CacheFieldChange::create('email', 'old@example.com', 'new@example.com')
        );

        $commands = iterator_to_array($this->resolver->resolveRefreshCommands(
            $customer,
            CacheInvalidationRule::OPERATION_UPDATED,
            $changeSet
        ));

        self::assertCount(2, $commands);
        self::assertSame(CustomerCachePolicyCollection::FAMILY_DETAIL, $commands[0]->family());
        self::assertSame('customer_id', $commands[0]->identifierName());
        self::assertSame((string) $customer->getUlid(), $commands[0]->identifierValue());
        self::assertSame('odm_change_set', $commands[0]->sourceName());
        self::assertSame(CustomerCachePolicyCollection::FAMILY_LOOKUP, $commands[1]->family());
        self::assertSame('email', $commands[1]->identifierName());
        self::assertSame('new@example.com', $commands[1]->identifierValue());
        self::assertSame(
            hash('sha256', implode('|', [
                CustomerCacheInvalidationRuleCollection::OPERATION_UPDATED,
                (string) $customer->getUlid(),
                'new@example.com',
            ])),
            $commands[1]->sourceId()
        );
        self::assertSame($commands[0]->sourceId(), $commands[1]->sourceId());
        self::assertSame($commands[0]->occurredOn(), $commands[1]->occurredOn());
        self::assertSame(
            CustomerCachePolicyCollection::REFRESH_SOURCE_REPOSITORY,
            $commands[1]->refreshSource()
        );
    }

    public function testResolveTagsIgnoresNonStringPreviousEmailChange(): void
    {
        $customer = $this->customer('fallback@example.com');
        $changeSet = CacheChangeSet::create(
            CacheFieldChange::create('email', 123, 'current@example.com')
        );

        $tags = iterator_to_array($this->resolver->resolveTags(
            $customer,
            CacheInvalidationRule::OPERATION_UPDATED,
            $changeSet
        ));

        self::assertSame([
            'customer.' . $customer->getUlid(),
            'customer.email.' . $this->cacheKeyBuilder->hashEmail('current@example.com'),
            'customer.collection',
        ], $tags);
    }

    public function testResolveForCustomerIdentifiersIncludesCurrentAndPreviousEmailTags(): void
    {
        $customerId = (string) $this->faker->ulid();

        $tags = iterator_to_array($this->resolver->resolveForCustomerIdentifiers(
            $customerId,
            'current@example.com',
            'previous@example.com'
        ));

        self::assertSame([
            'customer.' . $customerId,
            'customer.email.' . $this->cacheKeyBuilder->hashEmail('current@example.com'),
            'customer.email.' . $this->cacheKeyBuilder->hashEmail('previous@example.com'),
            'customer.collection',
        ], $tags);
    }

    public function testResolveForCustomerIdentifiersIgnoresCaseOnlyPreviousEmailChange(): void
    {
        $customerId = (string) $this->faker->ulid();

        $tags = iterator_to_array($this->resolver->resolveForCustomerIdentifiers(
            $customerId,
            'current@example.com',
            'Current@Example.COM'
        ));

        self::assertSame([
            'customer.' . $customerId,
            'customer.email.' . $this->cacheKeyBuilder->hashEmail('current@example.com'),
            'customer.collection',
        ], $tags);
    }

    public function testResolveForCustomerIdentifiersKeepsPreviousEmailWhenCurrentEmailIsMissing(): void
    {
        $customerId = (string) $this->faker->ulid();

        $tags = iterator_to_array($this->resolver->resolveForCustomerIdentifiers(
            $customerId,
            null,
            'previous@example.com'
        ));

        self::assertSame([
            'customer.' . $customerId,
            'customer.email.' . $this->cacheKeyBuilder->hashEmail('previous@example.com'),
            'customer.collection',
        ], $tags);
    }

    public function testResolveForChangeSetUsesCurrentEmailChangeBeforeIdentifierEmail(): void
    {
        $tags = iterator_to_array($this->resolver->resolveForChangeSet(
            [
                'customer_id' => null,
                'email' => 'identifier@example.com',
            ],
            [
                'email' => [null, 'current@example.com'],
            ]
        ));

        self::assertSame([
            'customer.email.' . $this->cacheKeyBuilder->hashEmail('current@example.com'),
            'customer.collection',
        ], $tags);
    }

    public function testResolveForChangeSetIncludesPreviousEmailWhenCurrentChangeIsNotString(): void
    {
        $tags = iterator_to_array($this->resolver->resolveForChangeSet(
            [
                'customer_id' => null,
                'email' => 'identifier@example.com',
            ],
            [
                'email' => ['previous@example.com', 123],
            ]
        ));

        self::assertSame([
            'customer.email.' . $this->cacheKeyBuilder->hashEmail('identifier@example.com'),
            'customer.email.' . $this->cacheKeyBuilder->hashEmail('previous@example.com'),
            'customer.collection',
        ], $tags);
    }

    public function testResolveForChangeSetIncludesPreviousEmailWhenOnlyPreviousChangeExists(): void
    {
        $tags = iterator_to_array($this->resolver->resolveForChangeSet(
            [
                'customer_id' => null,
                'email' => 'identifier@example.com',
            ],
            [
                'email' => ['previous@example.com'],
            ]
        ));

        self::assertSame([
            'customer.email.' . $this->cacheKeyBuilder->hashEmail('identifier@example.com'),
            'customer.email.' . $this->cacheKeyBuilder->hashEmail('previous@example.com'),
            'customer.collection',
        ], $tags);
    }

    public function testResolveForChangeSetFallsBackToIdentifierEmail(): void
    {
        $tags = iterator_to_array($this->resolver->resolveForChangeSet(
            [
                'customer_id' => '',
                'email' => 'identifier@example.com',
            ],
            [
                'email' => [123, false],
            ]
        ));

        self::assertSame([
            'customer.email.' . $this->cacheKeyBuilder->hashEmail('identifier@example.com'),
            'customer.collection',
        ], $tags);
    }

    public function testResolveForCustomerIdentifiersKeepsOnlyCollectionWithoutIdentifiers(): void
    {
        self::assertSame([
            'customer.collection',
        ], iterator_to_array($this->resolver->resolveForCustomerIdentifiers(null, null, null)));
    }

    public function testResolveRefreshCommandsOmitsLookupWhenEmailIsEmpty(): void
    {
        $customer = $this->customer('valid@example.com');
        $email = new ReflectionProperty(Customer::class, 'email');
        $email->setValue($customer, '');

        $commands = iterator_to_array($this->resolver->resolveRefreshCommands(
            $customer,
            CacheInvalidationRule::OPERATION_UPDATED,
            CacheChangeSet::empty()
        ));

        self::assertCount(1, $commands);
        self::assertSame(CustomerCachePolicyCollection::FAMILY_DETAIL, $commands[0]->family());
    }

    public function testResolveRefreshCommandsSourceIdDiffersByOperation(): void
    {
        $customer = $this->customer('same@example.com');

        $updatedCommands = iterator_to_array($this->resolver->resolveRefreshCommands(
            $customer,
            CacheInvalidationRule::OPERATION_UPDATED,
            CacheChangeSet::empty()
        ));
        $deletedCommands = iterator_to_array($this->resolver->resolveRefreshCommands(
            $customer,
            CacheInvalidationRule::OPERATION_DELETED,
            CacheChangeSet::empty()
        ));

        self::assertSame(
            hash('sha256', implode('|', [
                CacheInvalidationRule::OPERATION_UPDATED,
                (string) $customer->getUlid(),
                'same@example.com',
            ])),
            $updatedCommands[0]->sourceId()
        );
        self::assertSame(
            hash('sha256', implode('|', [
                CacheInvalidationRule::OPERATION_DELETED,
                (string) $customer->getUlid(),
                'same@example.com',
            ])),
            $deletedCommands[0]->sourceId()
        );
        self::assertNotSame($updatedCommands[0]->sourceId(), $deletedCommands[0]->sourceId());
    }

    private function customer(string $email): Customer
    {
        return new Customer(
            initials: 'Test Customer',
            email: $email,
            phone: '+1234567890',
            leadSource: 'test',
            type: new CustomerType('business', new Ulid((string) $this->faker->ulid())),
            status: new CustomerStatus('active', new Ulid((string) $this->faker->ulid())),
            confirmed: true,
            ulid: new Ulid((string) $this->faker->ulid())
        );
    }
}
