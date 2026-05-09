<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Infrastructure\Resolver;

use App\Core\Customer\Domain\Event\CustomerCreatedEvent;
use App\Core\Customer\Domain\Event\CustomerDeletedEvent;
use App\Core\Customer\Domain\Event\CustomerUpdatedEvent;
use App\Core\Customer\Infrastructure\Collection\CustomerCachePolicyCollection;
use App\Core\Customer\Infrastructure\Resolver\CustomerCacheRefreshTargetResolver;
use App\Tests\Unit\UnitTestCase;

final class CustomerCacheRefreshTargetResolverTest extends UnitTestCase
{
    private CustomerCacheRefreshTargetResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new CustomerCacheRefreshTargetResolver();
    }

    public function testSupportsOnlyCustomerDetailAndLookupFamilies(): void
    {
        self::assertTrue($this->resolver->supports('customer', 'detail'));
        self::assertTrue($this->resolver->supports('customer', 'lookup'));
        self::assertFalse($this->resolver->supports('customer', 'collection'));
        self::assertFalse($this->resolver->supports('order', 'detail'));
    }

    public function testResolveMapsCustomerIdToDetailTarget(): void
    {
        $target = $this->resolver->resolve(
            CustomerCachePolicyCollection::CONTEXT,
            CustomerCachePolicyCollection::FAMILY_DETAIL,
            'customer_id',
            'customer-1'
        );

        self::assertSame(CustomerCachePolicyCollection::CONTEXT, $target->context());
        self::assertSame(CustomerCachePolicyCollection::FAMILY_DETAIL, $target->family());
        self::assertSame('customer_id', $target->identifierName());
        self::assertSame('customer-1', $target->identifierValue());
    }

    public function testResolveMapsEmailToLookupTarget(): void
    {
        $target = $this->resolver->resolve(
            CustomerCachePolicyCollection::CONTEXT,
            CustomerCachePolicyCollection::FAMILY_LOOKUP,
            'email',
            'customer@example.com'
        );

        self::assertSame(CustomerCachePolicyCollection::CONTEXT, $target->context());
        self::assertSame(CustomerCachePolicyCollection::FAMILY_LOOKUP, $target->family());
        self::assertSame('email', $target->identifierName());
        self::assertSame('customer@example.com', $target->identifierValue());
    }

    public function testCreatedEventWithEmptyEmailResolvesOnlyDetailTarget(): void
    {
        $targets = $this->resolver->resolveForCreatedEvent(new CustomerCreatedEvent(
            customerId: 'customer-1',
            customerEmail: ''
        ));

        self::assertCount(1, $targets);
        self::assertSame(CustomerCachePolicyCollection::FAMILY_DETAIL, $targets[0]['family']);
        self::assertSame(['customer_id' => 'customer-1'], $targets[0]['identifiers']);
    }

    public function testUpdatedEventWithEmptyCurrentEmailKeepsPreviousEmailLookupTarget(): void
    {
        $targets = $this->resolver->resolveForUpdatedEvent(new CustomerUpdatedEvent(
            customerId: 'customer-1',
            currentEmail: '',
            previousEmail: 'previous@example.com'
        ));

        self::assertCount(2, $targets);
        self::assertSame(CustomerCachePolicyCollection::FAMILY_DETAIL, $targets[0]['family']);
        self::assertSame(['customer_id' => 'customer-1'], $targets[0]['identifiers']);
        self::assertSame(CustomerCachePolicyCollection::FAMILY_LOOKUP, $targets[1]['family']);
        self::assertSame(['email' => 'previous@example.com'], $targets[1]['identifiers']);
    }

    public function testDeletedEventWithEmptyEmailResolvesOnlyDetailTarget(): void
    {
        $targets = $this->resolver->resolveForDeletedEvent(new CustomerDeletedEvent(
            customerId: 'customer-1',
            customerEmail: ''
        ));

        self::assertCount(1, $targets);
        self::assertSame(CustomerCachePolicyCollection::FAMILY_DETAIL, $targets[0]['family']);
        self::assertSame(['customer_id' => 'customer-1'], $targets[0]['identifiers']);
    }

    /**
     * @dataProvider unsupportedTargetsProvider
     */
    public function testResolveRejectsUnsupportedTargets(
        string $context,
        string $family,
        string $identifierName,
        string $identifierValue
    ): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Unsupported cache refresh target "%s.%s" for identifier "%s".',
            $context,
            $family,
            $identifierName
        ));

        $this->resolver->resolve($context, $family, $identifierName, $identifierValue);
    }

    /**
     * @return iterable<string, array{string, string, string, string}>
     */
    public static function unsupportedTargetsProvider(): iterable
    {
        yield 'empty identifier value' => [
            CustomerCachePolicyCollection::CONTEXT,
            CustomerCachePolicyCollection::FAMILY_LOOKUP,
            'email',
            '',
        ];
        yield 'empty identifier name' => [
            CustomerCachePolicyCollection::CONTEXT,
            CustomerCachePolicyCollection::FAMILY_LOOKUP,
            '',
            'customer@example.com',
        ];
        yield 'unknown identifier' => [
            CustomerCachePolicyCollection::CONTEXT,
            CustomerCachePolicyCollection::FAMILY_LOOKUP,
            'username',
            'customer@example.com',
        ];
        yield 'wrong family for identifier' => [
            CustomerCachePolicyCollection::CONTEXT,
            CustomerCachePolicyCollection::FAMILY_DETAIL,
            'email',
            'customer@example.com',
        ];
        yield 'unsupported context' => [
            'order',
            CustomerCachePolicyCollection::FAMILY_DETAIL,
            'customer_id',
            'customer-1',
        ];
    }
}
