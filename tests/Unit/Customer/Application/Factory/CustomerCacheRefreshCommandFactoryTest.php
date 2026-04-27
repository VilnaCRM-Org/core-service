<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Factory;

use App\Core\Customer\Application\Factory\CustomerCacheRefreshCommandFactory;
use App\Core\Customer\Domain\Event\CustomerCreatedEvent;
use App\Core\Customer\Domain\Event\CustomerDeletedEvent;
use App\Core\Customer\Domain\Event\CustomerUpdatedEvent;
use App\Core\Customer\Infrastructure\Collection\CustomerCachePolicyCollection;
use App\Core\Customer\Infrastructure\Resolver\CustomerCacheRefreshTargetResolver;
use App\Tests\Unit\UnitTestCase;
use InvalidArgumentException;
use ReflectionMethod;

final class CustomerCacheRefreshCommandFactoryTest extends UnitTestCase
{
    private CustomerCacheRefreshCommandFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new CustomerCacheRefreshCommandFactory(
            new CustomerCacheRefreshTargetResolver()
        );
    }

    public function testCreatesDetailAndLookupRefreshCommandsForCreatedEvent(): void
    {
        $event = new CustomerCreatedEvent(
            'customer-1',
            'created@example.com',
            'event-1',
            '2026-04-27T10:00:00+00:00'
        );

        $commands = iterator_to_array($this->factory->createForCreatedEvent($event));

        self::assertCount(2, $commands);
        self::assertSame(CustomerCachePolicyCollection::FAMILY_DETAIL, $commands[0]->family());
        self::assertSame('customer_id', $commands[0]->identifierName());
        self::assertSame('customer-1', $commands[0]->identifierValue());
        self::assertSame(CustomerCachePolicyCollection::FAMILY_LOOKUP, $commands[1]->family());
        self::assertSame('email', $commands[1]->identifierName());
        self::assertSame('created@example.com', $commands[1]->identifierValue());
        self::assertSame('customer.created', $commands[0]->sourceName());
        self::assertSame('event-1', $commands[0]->sourceId());
    }

    public function testCreatesPreviousEmailLookupRefreshForUpdatedEvent(): void
    {
        $event = new CustomerUpdatedEvent(
            'customer-1',
            'new@example.com',
            'old@example.com',
            'event-2',
            '2026-04-27T10:00:00+00:00'
        );

        $commands = iterator_to_array($this->factory->createForUpdatedEvent($event));

        self::assertCount(3, $commands);
        self::assertSame('new@example.com', $commands[1]->identifierValue());
        self::assertSame('old@example.com', $commands[2]->identifierValue());
        self::assertSame(
            CustomerCachePolicyCollection::REFRESH_SOURCE_REPOSITORY,
            $commands[2]->refreshSource()
        );
    }

    public function testDeletedEventCreatesInvalidateOnlyRefreshCommands(): void
    {
        $event = new CustomerDeletedEvent(
            'customer-1',
            'deleted@example.com',
            'event-3',
            '2026-04-27T10:00:00+00:00'
        );

        $commands = iterator_to_array($this->factory->createForDeletedEvent($event));

        self::assertCount(2, $commands);
        self::assertSame(
            CustomerCachePolicyCollection::REFRESH_SOURCE_INVALIDATE_ONLY,
            $commands[0]->refreshSource()
        );
        self::assertSame(
            CustomerCachePolicyCollection::REFRESH_SOURCE_INVALIDATE_ONLY,
            $commands[1]->refreshSource()
        );
    }

    public function testRejectsTargetWithoutIdentifiers(): void
    {
        $event = new CustomerCreatedEvent(
            'customer-1',
            'created@example.com',
            'event-1',
            '2026-04-27T10:00:00+00:00'
        );
        $method = new ReflectionMethod($this->factory, 'createCommand');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Missing identifiers in createCommand; targetResolver/createRefreshCommand need metadata. Target "customer.detail".'
        );

        $method->invoke($this->factory, [
            'context' => CustomerCachePolicyCollection::CONTEXT,
            'family' => CustomerCachePolicyCollection::FAMILY_DETAIL,
            'identifiers' => [],
        ], CustomerCachePolicyCollection::REFRESH_SOURCE_REPOSITORY, $event);
    }
}
