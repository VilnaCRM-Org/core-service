<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Filter;

use App\Customer\Domain\Entity\Customer;
use App\Shared\Infrastructure\Filter\UlidRangeFilter;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage\MatchStage;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

final class UlidRangeFilterTest extends TestCase
{
    /** @var ManagerRegistry|MockObject */
    private $managerRegistry;

    /** @var LoggerInterface|MockObject */
    private $logger;

    /** @var NameConverterInterface|MockObject */
    private $nameConverter;

    /** @var Builder|MockObject */
    private $builder;

    /** @var MatchStage|MockObject */
    private $matchStage;

    protected function setUp(): void
    {
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->nameConverter = $this->createMock(NameConverterInterface::class);
        $this->builder = $this->createMock(Builder::class);
        $this->matchStage = $this->createMock(MatchStage::class);
    }

    public function testGetDescription(): void
    {
        // Use the real filter instance for description tests.
        $filter = new UlidRangeFilter(
            $this->managerRegistry,
            $this->logger,
            ['ulid' => null],
            $this->nameConverter
        );

        $description = $filter->getDescription(Customer::class);

        $this->assertArrayHasKey('ulid[lt]', $description);
        $this->assertArrayHasKey('ulid[lte]', $description);
        $this->assertArrayHasKey('ulid[gt]', $description);
        $this->assertArrayHasKey('ulid[gte]', $description);
        $this->assertArrayHasKey('ulid[between]', $description);

        $ltDescription = $description['ulid[lt]'];
        $this->assertEquals('ulid', $ltDescription['property']);
        $this->assertEquals('string', $ltDescription['type']);
        $this->assertFalse($ltDescription['required']);
        $this->assertEquals('Filter on the ulid property using the lt operator', $ltDescription['description']);
    }

    public function testGetDescriptionWithNoProperties(): void
    {
        $filter = new UlidRangeFilter(
            $this->managerRegistry,
            $this->logger,
            [],
            $this->nameConverter
        );
        $description = $filter->getDescription(Customer::class);
        $this->assertEmpty($description);
    }

    /**
     * Helper to create a partial mock of UlidRangeFilter that always considers properties filterable.
     *
     * @param array<string, mixed> $properties
     * @return UlidRangeFilter
     */
    private function createFilterWithMapping(array $properties): UlidRangeFilter
    {
        $filter = $this->getMockBuilder(UlidRangeFilter::class)
            ->setConstructorArgs([
                $this->managerRegistry,
                $this->logger,
                $properties,
                $this->nameConverter,
            ])
            ->onlyMethods(['isPropertyEnabled', 'isPropertyMapped', 'denormalizePropertyName'])
            ->getMock();

        // Return the property unchanged.
        $filter->method('denormalizePropertyName')->willReturnArgument(0);
        // Always consider the property enabled and mapped.
        $filter->method('isPropertyEnabled')->willReturn(true);
        $filter->method('isPropertyMapped')->willReturn(true);

        return $filter;
    }

    public function testApplyWithSingleValue(): void
    {
        $filter = $this->createFilterWithMapping(['ulid' => null]);

        $value = ['lt' => '01JKX8XGHVDZ46MWYMZT94YER4'];
        $context = [
            'filters' => [
                'ulid' => $value
            ]
        ];

        $this->builder->expects($this->once())
            ->method('match')
            ->willReturn($this->matchStage);

        $this->matchStage->expects($this->once())
            ->method('field')
            ->with('ulid')
            ->willReturnSelf();

        $this->matchStage->expects($this->once())
            ->method('lt')
            ->with('01JKX8XGHVDZ46MWYMZT94YER4')
            ->willReturnSelf();

        $filter->apply($this->builder, Customer::class, null, $context);
    }

    public function testApplyWithMultipleValues(): void
    {
        $filter = $this->createFilterWithMapping(['ulid' => null]);

        $value = [
            'lt' => '01JKX8XGHVDZ46MWYMZT94YER4',
            'gt' => '01JKX8XGHVDZ46MWYMZT94YER3'
        ];
        $context = [
            'filters' => [
                'ulid' => $value
            ]
        ];

        // Now expect match() to be called for each operator (2 times).
        $this->builder->expects($this->exactly(2))
            ->method('match')
            ->willReturn($this->matchStage);

        $this->matchStage->expects($this->exactly(2))
            ->method('field')
            ->with('ulid')
            ->willReturnSelf();

        $this->matchStage->expects($this->once())
            ->method('lt')
            ->with('01JKX8XGHVDZ46MWYMZT94YER4')
            ->willReturnSelf();

        $this->matchStage->expects($this->once())
            ->method('gt')
            ->with('01JKX8XGHVDZ46MWYMZT94YER3')
            ->willReturnSelf();

        $filter->apply($this->builder, Customer::class, null, $context);
    }

    public function testApplyWithAllOperators(): void
    {
        $filter = $this->createFilterWithMapping(['ulid' => null]);

        $value = [
            'lt' => '01JKX8XGHVDZ46MWYMZT94YER4',
            'lte' => '01JKX8XGHVDZ46MWYMZT94YER5',
            'gt' => '01JKX8XGHVDZ46MWYMZT94YER3',
            'gte' => '01JKX8XGHVDZ46MWYMZT94YER2',
            'between' => '01JKX8XGHVDZ46MWYMZT94YER1..01JKX8XGHVDZ46MWYMZT94YER6'
        ];
        $context = [
            'filters' => [
                'ulid' => $value
            ]
        ];

        // We expect one match() call per operator (5 total)
        $this->builder->expects($this->exactly(5))
            ->method('match')
            ->willReturn($this->matchStage);

        // Each operator triggers a field() call.
        $this->matchStage->expects($this->exactly(5))
            ->method('field')
            ->with('ulid')
            ->willReturnSelf();

        // Expect lt and gt to be called once.
        $this->matchStage->expects($this->once())
            ->method('lt')
            ->with('01JKX8XGHVDZ46MWYMZT94YER4')
            ->willReturnSelf();

        $this->matchStage->expects($this->once())
            ->method('gt')
            ->with('01JKX8XGHVDZ46MWYMZT94YER3')
            ->willReturnSelf();

        // For lte: one call from the explicit 'lte' operator and one from the 'between' operator.
        $this->matchStage->expects($this->exactly(2))
            ->method('lte')
            ->withConsecutive(
                ['01JKX8XGHVDZ46MWYMZT94YER5'],
                ['01JKX8XGHVDZ46MWYMZT94YER6']
            )
            ->willReturnSelf();

        // For gte: one call from the explicit 'gte' operator and one from the 'between' operator.
        $this->matchStage->expects($this->exactly(2))
            ->method('gte')
            ->withConsecutive(
                ['01JKX8XGHVDZ46MWYMZT94YER2'],
                ['01JKX8XGHVDZ46MWYMZT94YER1']
            )
            ->willReturnSelf();

        // Remove any expectation for a "range" call, since "between" now triggers gte and lte calls.
        $filter->apply($this->builder, Customer::class, null, $context);
    }

    public function testApplyWithNonFilterableProperty(): void
    {
        // Using the default filter instance (without overriding) so that the property is not filterable.
        $filter = new UlidRangeFilter(
            $this->managerRegistry,
            $this->logger,
            ['ulid' => null],
            $this->nameConverter
        );

        $value = ['lt' => 'test@example.com'];
        $context = [
            'filters' => [
                'email' => $value
            ]
        ];

        $this->builder->expects($this->never())
            ->method('match');

        $filter->apply($this->builder, Customer::class, null, $context);
    }

    public function testApplyWithNonArrayValue(): void
    {
        $filter = new UlidRangeFilter(
            $this->managerRegistry,
            $this->logger,
            ['ulid' => null],
            $this->nameConverter
        );

        $value = '01JKX8XGHVDZ46MWYMZT94YER4';
        $context = [
            'filters' => [
                'ulid' => $value
            ]
        ];

        $this->builder->expects($this->never())
            ->method('match');

        $filter->apply($this->builder, Customer::class, null, $context);
    }

    public function testApplyWithEmptyContext(): void
    {
        $filter = new UlidRangeFilter(
            $this->managerRegistry,
            $this->logger,
            ['ulid' => null],
            $this->nameConverter
        );

        $context = ['filters' => []];

        $this->builder->expects($this->never())
            ->method('match');

        $filter->apply($this->builder, Customer::class, null, $context);
    }

    public function testApplyWithEmptyFilters(): void
    {
        $filter = new UlidRangeFilter(
            $this->managerRegistry,
            $this->logger,
            ['ulid' => null],
            $this->nameConverter
        );

        $context = ['filters' => []];

        $this->builder->expects($this->never())
            ->method('match');

        $filter->apply($this->builder, Customer::class, null, $context);
    }

    public function testApplyWithNestedProperty(): void
    {
        $filter = $this->createFilterWithMapping(['customer.ulid' => null]);

        $value = ['lt' => '01JKX8XGHVDZ46MWYMZT94YER4'];
        $context = [
            'filters' => [
                'customer.ulid' => $value
            ]
        ];

        $this->builder->expects($this->once())
            ->method('match')
            ->willReturn($this->matchStage);

        $this->matchStage->expects($this->once())
            ->method('field')
            ->with('customer.ulid')
            ->willReturnSelf();

        $this->matchStage->expects($this->once())
            ->method('lt')
            ->with('01JKX8XGHVDZ46MWYMZT94YER4')
            ->willReturnSelf();

        $filter->apply($this->builder, Customer::class, null, $context);
    }

    public function testApplyWithDeeplyNestedProperty(): void
    {
        $filter = $this->createFilterWithMapping(['customer.address.ulid' => null]);

        $value = ['lt' => '01JKX8XGHVDZ46MWYMZT94YER4'];
        $context = [
            'filters' => [
                'customer.address.ulid' => $value
            ]
        ];

        $this->builder->expects($this->once())
            ->method('match')
            ->willReturn($this->matchStage);

        $this->matchStage->expects($this->once())
            ->method('field')
            ->with('customer.address.ulid')
            ->willReturnSelf();

        $this->matchStage->expects($this->once())
            ->method('lt')
            ->with('01JKX8XGHVDZ46MWYMZT94YER4')
            ->willReturnSelf();

        $filter->apply($this->builder, Customer::class, null, $context);
    }

    public function testApplyWithInvalidOperator(): void
    {
        $filter = new UlidRangeFilter(
            $this->managerRegistry,
            $this->logger,
            ['ulid' => null],
            $this->nameConverter
        );

        $value = ['invalid' => '01JKX8XGHVDZ46MWYMZT94YER4'];
        $context = [
            'filters' => [
                'ulid' => $value
            ]
        ];

        $this->builder->expects($this->never())
            ->method('match');

        $filter->apply($this->builder, Customer::class, null, $context);
    }

    public function testApplyWithNullValue(): void
    {
        $filter = new UlidRangeFilter(
            $this->managerRegistry,
            $this->logger,
            ['ulid' => null],
            $this->nameConverter
        );

        $value = ['lt' => null];
        $context = [
            'filters' => [
                'ulid' => $value
            ]
        ];

        $this->builder->expects($this->never())
            ->method('match');

        $filter->apply($this->builder, Customer::class, null, $context);
    }

    public function testApplyWithEmptyValue(): void
    {
        $filter = new UlidRangeFilter(
            $this->managerRegistry,
            $this->logger,
            ['ulid' => null],
            $this->nameConverter
        );

        $value = ['lt' => ''];
        $context = [
            'filters' => [
                'ulid' => $value
            ]
        ];

        $this->builder->expects($this->never())
            ->method('match');

        $filter->apply($this->builder, Customer::class, null, $context);
    }

    public function testApplyWithInvalidUlidFormat(): void
    {
        $filter = new UlidRangeFilter(
            $this->managerRegistry,
            $this->logger,
            ['ulid' => null],
            $this->nameConverter
        );

        $value = ['lt' => 'invalid-ulid'];
        $context = [
            'filters' => [
                'ulid' => $value
            ]
        ];

        $this->builder->expects($this->never())
            ->method('match');

        $filter->apply($this->builder, Customer::class, null, $context);
    }

    public function testApplyWithOperation(): void
    {
        $filter = $this->createFilterWithMapping(['ulid' => null]);

        $value = ['lt' => '01JKX8XGHVDZ46MWYMZT94YER4'];
        $context = [
            'filters' => [
                'ulid' => $value
            ]
        ];
        $operation = $this->createMock(\ApiPlatform\Metadata\Operation::class);

        $this->builder->expects($this->once())
            ->method('match')
            ->willReturn($this->matchStage);

        $this->matchStage->expects($this->once())
            ->method('field')
            ->with('ulid')
            ->willReturnSelf();

        $this->matchStage->expects($this->once())
            ->method('lt')
            ->with('01JKX8XGHVDZ46MWYMZT94YER4')
            ->willReturnSelf();

        $filter->apply($this->builder, Customer::class, $operation, $context);
    }

    public function testApplyWithMultipleProperties(): void
    {
        $filter = $this->createFilterWithMapping(['ulid' => null, 'customer.ulid' => null]);

        $context = [
            'filters' => [
                'ulid' => ['lt' => '01JKX8XGHVDZ46MWYMZT94YER4'],
                'customer.ulid' => ['lt' => '01JKX8XGHVDZ46MWYMZT94YER4']
            ]
        ];

        // Expect two match() calls (one per property).
        $this->builder->expects($this->exactly(2))
            ->method('match')
            ->willReturn($this->matchStage);

        $this->matchStage->expects($this->exactly(2))
            ->method('field')
            ->withConsecutive(['ulid'], ['customer.ulid'])
            ->willReturnSelf();

        $this->matchStage->expects($this->exactly(2))
            ->method('lt')
            ->with('01JKX8XGHVDZ46MWYMZT94YER4')
            ->willReturnSelf();

        $filter->apply($this->builder, Customer::class, null, $context);
    }
}
