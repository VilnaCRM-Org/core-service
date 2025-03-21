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
        $filter = $this->createFilter(['ulid' => null]);
        $description = $filter->getDescription(Customer::class);

        $this->assertArrayHasKey('ulid[lt]', $description);
        $this->assertArrayHasKey('ulid[lte]', $description);
        $this->assertArrayHasKey('ulid[gt]', $description);
        $this->assertArrayHasKey('ulid[gte]', $description);
        $this->assertArrayHasKey('ulid[between]', $description);

        $this->assertDescriptionContent($description['ulid[lt]']);
    }

    /**
     * @param array<string, string|bool> $description
     */
    private function assertDescriptionContent(array $description): void
    {
        $this->assertEquals('ulid', $description['property']);
        $this->assertEquals('string', $description['type']);
        $this->assertFalse($description['required']);
        $this->assertEquals(
            'Filter on the ulid property using the lt operator',
            $description['description']
        );
    }

    public function testGetDescriptionWithNoProperties(): void
    {
        $filter = $this->createFilter([]);
        $description = $filter->getDescription(Customer::class);
        $this->assertEmpty($description);
    }

    /**
     * @param array<string, null> $properties
     */
    private function createFilter(array $properties): UlidRangeFilter
    {
        return new UlidRangeFilter(
            $this->managerRegistry,
            $this->logger,
            $properties,
            $this->nameConverter
        );
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
            ->onlyMethods([
                'isPropertyEnabled',
                'isPropertyMapped',
                'denormalizePropertyName',
            ])
            ->getMock();

        $filter->method('denormalizePropertyName')->willReturnArgument(0);
        $filter->method('isPropertyEnabled')->willReturn(true);
        $filter->method('isPropertyMapped')->willReturn(true);

        return $filter;
    }

    public function testApplyWithSingleValue(): void
    {
        $filter = $this->createFilterWithMapping(['ulid' => null]);
        $context = $this->buildContext([
            'ulid' => ['lt' => '01JKX8XGHVDZ46MWYMZT94YER4'],
        ]);

        $this->setupSingleMatchExpectation('ulid', 'lt', '01JKX8XGHVDZ46MWYMZT94YER4');

        $filter->apply($this->builder, Customer::class, null, $context);
    }

    /**
     * @return array{0: UlidRangeFilter, 1: array<string, array<string, string>>}
     */
    private function setupMultipleValuesTest(): array
    {
        $filter = $this->createFilterWithMapping(['ulid' => null]);
        $value = [
            'lt' => '01JKX8XGHVDZ46MWYMZT94YER4',
            'gt' => '01JKX8XGHVDZ46MWYMZT94YER3',
        ];
        $context = $this->buildContext(['ulid' => $value]);

        return [$filter, $context];
    }

    private function setupMultipleValuesExpectations(): void
    {
        $this->setupMatchExpectations(2, 'ulid');

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
        [$filter, $context] = $this->setupMultipleValuesTest();
        $this->setupMultipleValuesExpectations();

        $filter->apply($this->builder, Customer::class, null, $context);
    }

    /**
     * @return array{0: UlidRangeFilter, 1: array<string, array<string, string>>}
     */
    private function setupAllOperatorsTest(): array
    {
        $filter = $this->createFilterWithMapping(['ulid' => null]);
        $value = [
            'lt' => '01JKX8XGHVDZ46MWYMZT94YER4',
            'lte' => '01JKX8XGHVDZ46MWYMZT94YER5',
            'gt' => '01JKX8XGHVDZ46MWYMZT94YER3',
            'gte' => '01JKX8XGHVDZ46MWYMZT94YER2',
            'between' => '01JKX8XGHVDZ46MWYMZT94YER1..01JKX8XGHVDZ46MWYMZT94YER6',
        ];
        $context = $this->buildContext(['ulid' => $value]);

        return [$filter, $context];
    }

    private function setupAllOperatorsExpectations(): void
    {
        $this->setupMatchExpectations(5, 'ulid');
        $this->setupLtGtExpectations();
        $this->setupLteExpectations();
        $this->setupGteExpectations();
    }

    private function setupLtGtExpectations(): void
    {
        $this->matchStage->expects($this->once())
            ->method('lt')
            ->with('01JKX8XGHVDZ46MWYMZT94YER4')
            ->willReturnSelf();

        $this->matchStage->expects($this->once())
            ->method('gt')
            ->with('01JKX8XGHVDZ46MWYMZT94YER3')
            ->willReturnSelf();
    }

    private function setupLteExpectations(): void
    {
        $this->matchStage->expects($this->exactly(2))
            ->method('lte')
            ->withConsecutive(
                ['01JKX8XGHVDZ46MWYMZT94YER5'],
                ['01JKX8XGHVDZ46MWYMZT94YER6']
            )
            ->willReturnSelf();
    }

    private function setupGteExpectations(): void
    {
        $this->matchStage->expects($this->exactly(2))
            ->method('gte')
            ->withConsecutive(
                ['01JKX8XGHVDZ46MWYMZT94YER2'],
                ['01JKX8XGHVDZ46MWYMZT94YER1']
            )
            ->willReturnSelf();
    }

    public function testApplyWithAllOperators(): void
    {
        [$filter, $context] = $this->setupAllOperatorsTest();
        $this->setupAllOperatorsExpectations();

        $filter->apply($this->builder, Customer::class, null, $context);
    }

    public function testApplyWithNonFilterableProperty(): void
    {
        $filter = $this->createFilter(['ulid' => null]);
        $context = $this->buildContext(['email' => ['lt' => 'test@example.com']]);

        $this->builder->expects($this->never())->method('match');

        $filter->apply($this->builder, Customer::class, null, $context);
    }

    public function testApplyWithNonArrayValue(): void
    {
        $filter = $this->createFilter(['ulid' => null]);
        $context = $this->buildContext(['ulid' => '01JKX8XGHVDZ46MWYMZT94YER4']);

        $this->builder->expects($this->never())->method('match');

        $filter->apply($this->builder, Customer::class, null, $context);
    }

    public function testApplyWithEmptyContext(): void
    {
        $filter = $this->createFilter(['ulid' => null]);
        $context = ['filters' => []];

        $this->builder->expects($this->never())->method('match');

        $filter->apply($this->builder, Customer::class, null, $context);
    }

    public function testApplyWithEmptyFilters(): void
    {
        $filter = $this->createFilter(['ulid' => null]);
        $context = ['filters' => []];

        $this->builder->expects($this->never())->method('match');

        $filter->apply($this->builder, Customer::class, null, $context);
    }

    public function testApplyWithNestedProperty(): void
    {
        $filter = $this->createFilterWithMapping(['customer.ulid' => null]);
        $context = $this->buildContext([
            'customer.ulid' => ['lt' => '01JKX8XGHVDZ46MWYMZT94YER4'],
        ]);

        $this->setupSingleMatchExpectation(
            'customer.ulid',
            'lt',
            '01JKX8XGHVDZ46MWYMZT94YER4'
        );

        $filter->apply($this->builder, Customer::class, null, $context);
    }

    public function testApplyWithDeeplyNestedProperty(): void
    {
        $filter = $this->createFilterWithMapping(['customer.address.ulid' => null]);
        $context = $this->buildContext([
            'customer.address.ulid' => ['lt' => '01JKX8XGHVDZ46MWYMZT94YER4'],
        ]);

        $this->setupSingleMatchExpectation(
            'customer.address.ulid',
            'lt',
            '01JKX8XGHVDZ46MWYMZT94YER4'
        );

        $filter->apply($this->builder, Customer::class, null, $context);
    }

    public function testApplyWithInvalidOperator(): void
    {
        $filter = $this->createFilter(['ulid' => null]);
        $context = $this->buildContext([
            'ulid' => ['invalid' => '01JKX8XGHVDZ46MWYMZT94YER4'],
        ]);

        $this->builder->expects($this->never())->method('match');

        $filter->apply($this->builder, Customer::class, null, $context);
    }

    public function testApplyWithNullValue(): void
    {
        $filter = $this->createFilter(['ulid' => null]);
        $context = $this->buildContext(['ulid' => ['lt' => null]]);

        $this->builder->expects($this->never())->method('match');

        $filter->apply($this->builder, Customer::class, null, $context);
    }

    public function testApplyWithEmptyValue(): void
    {
        $filter = $this->createFilter(['ulid' => null]);
        $context = $this->buildContext(['ulid' => ['lt' => '']]);

        $this->builder->expects($this->never())->method('match');

        $filter->apply($this->builder, Customer::class, null, $context);
    }

    public function testApplyWithInvalidUlidFormat(): void
    {
        $filter = $this->createFilter(['ulid' => null]);
        $context = $this->buildContext(['ulid' => ['lt' => 'invalid-ulid']]);

        $this->builder->expects($this->never())->method('match');

        $filter->apply($this->builder, Customer::class, null, $context);
    }

    public function testApplyWithOperation(): void
    {
        $filter = $this->createFilterWithMapping(['ulid' => null]);
        $context = $this->buildContext([
            'ulid' => ['lt' => '01JKX8XGHVDZ46MWYMZT94YER4'],
        ]);
        $operation = $this->createMock(Operation::class);

        $this->setupSingleMatchExpectation('ulid', 'lt', '01JKX8XGHVDZ46MWYMZT94YER4');

        $filter->apply($this->builder, Customer::class, $operation, $context);
    }

    public function testApplyWithMultipleProperties(): void
    {
        $filter = $this->createFilterWithMapping(['ulid' => null, 'customer.ulid' => null]);
        $context = $this->buildContext([
            'ulid' => ['lt' => '01JKX8XGHVDZ46MWYMZT94YER4'],
            'customer.ulid' => ['lt' => '01JKX8XGHVDZ46MWYMZT94YER4'],
        ]);

        $this->setupMultiplePropertiesExpectations();

        $filter->apply($this->builder, Customer::class, null, $context);
    }

    private function setupMultiplePropertiesExpectations(): void
    {
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
    }

    /**
     * @param array<string, array<string, string|null>|string> $filters
     * @return array<string, array<string, array<string, string|null>|string>>
     */
    private function buildContext(array $filters): array
    {
        return ['filters' => $filters];
    }

    private function setupMatchExpectations(int $times, string $field): void
    {
        $this->builder->expects($this->exactly($times))
            ->method('match')
            ->willReturn($this->matchStage);

        $this->matchStage->expects($this->exactly($times))
            ->method('field')
            ->with($field)
            ->willReturnSelf();
    }

    private function setupSingleMatchExpectation(
        string $field,
        string $operator,
        string $value
    ): void {
        $this->setupMatchExpectations(1, $field);

        $this->matchStage->expects($this->once())
            ->method($operator)
            ->with($value)
            ->willReturnSelf();
    }
}
