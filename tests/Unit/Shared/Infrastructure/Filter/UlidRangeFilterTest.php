<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Filter;

use ApiPlatform\Metadata\Operation;
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
    private ManagerRegistry|MockObject $managerRegistry;

    private LoggerInterface|MockObject $logger;

    private NameConverterInterface|MockObject $nameConverter;

    private Builder|MockObject $builder;

    private MatchStage|MockObject $matchStage;

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
     * @param array<string, null> $properties
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

        $filter->method('denormalizePropertyName')->willReturnArgument(0);
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

    private function setupMultipleValuesTest(): array
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

        return [$filter, $context];
    }

    private function setupMultipleValuesExpectations(): void
    {
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
    }

    public function testApplyWithMultipleValues(): void
    {
        list($filter, $context) = $this->setupMultipleValuesTest();
        $this->setupMultipleValuesExpectations();

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

        $this->builder->expects($this->exactly(5))
            ->method('match')
            ->willReturn($this->matchStage);

        $this->matchStage->expects($this->exactly(5))
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

        $this->matchStage->expects($this->exactly(2))
            ->method('lte')
            ->withConsecutive(
                ['01JKX8XGHVDZ46MWYMZT94YER5'],
                ['01JKX8XGHVDZ46MWYMZT94YER6']
            )
            ->willReturnSelf();

        $this->matchStage->expects($this->exactly(2))
            ->method('gte')
            ->withConsecutive(
                ['01JKX8XGHVDZ46MWYMZT94YER2'],
                ['01JKX8XGHVDZ46MWYMZT94YER1']
            )
            ->willReturnSelf();

        $filter->apply($this->builder, Customer::class, null, $context);
    }

    public function testApplyWithNonFilterableProperty(): void
    {
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
                'ulid' => $value,
            ],
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
                'ulid' => $value,
            ],
        ];
        $operation = $this->createMock(Operation::class);

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
                'customer.ulid' => ['lt' => '01JKX8XGHVDZ46MWYMZT94YER4'],
            ],
        ];

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
