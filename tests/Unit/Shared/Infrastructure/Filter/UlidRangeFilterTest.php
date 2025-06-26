<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Filter;

use ApiPlatform\Metadata\Operation;
use App\Core\Customer\Domain\Entity\Customer;
use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\Filter\UlidRangeFilter;
use App\Tests\Unit\UnitTestCase;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage\MatchStage;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

final class UlidRangeFilterTest extends UnitTestCase
{
    private ManagerRegistry|MockObject $managerRegistry;
    private LoggerInterface|MockObject $logger;
    private NameConverterInterface|MockObject $nameConverter;
    private Builder|MockObject $builder;
    private MatchStage|MockObject $matchStage;

    protected function setUp(): void
    {
        parent::setUp();

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

    public function testGetDescriptionWithNoProperties(): void
    {
        $filter = $this->createFilter([]);
        $description = $filter->getDescription(Customer::class);
        $this->assertEmpty($description);
    }

    public function testApplyWithSingleValue(): void
    {
        $filter = $this->createFilterWithMapping(['ulid' => null]);
        $ulidString = (string) $this->faker->ulid();
        $context = $this->buildContext([
            'ulid' => ['lt' => $ulidString],
        ]);

        $this->setupSingleMatchExpectation();

        $filter->apply($this->builder, Customer::class, null, $context);
    }

    public function testApplyWithMultipleValues(): void
    {
        [$filter, $context] = $this->setupMultipleValuesTest();
        $this->setupMultipleValuesExpectations();

        $filter->apply($this->builder, Customer::class, null, $context);
    }

    public function testApplyWithAllOperators(): void
    {
        [$filter, $context] = $this->setupAllOperatorsTest();
        $this->setupAllOperatorsExpectations();

        $filter->apply(
            $this->builder,
            Customer::class,
            null,
            $context
        );
    }

    public function testApplyWithNonFilterableProperty(): void
    {
        $filter = $this->createFilter(['ulid' => null]);
        $context = $this
            ->buildContext(['email' => ['lt' => $this->faker->email()]]);

        $this->builder->expects($this->never())->method('match');

        $filter->apply(
            $this->builder,
            Customer::class,
            null,
            $context
        );
    }

    public function testApplyWithNonArrayValue(): void
    {
        $filter = $this->createFilter(['ulid' => null]);
        $ulidValue = (string) $this->faker->ulid();
        $context = $this->buildContext(['ulid' => $ulidValue]);

        $this->builder->expects($this->never())->method('match');

        $filter->apply(
            $this->builder,
            Customer::class,
            null,
            $context
        );
    }

    public function testApplyWithEmptyContext(): void
    {
        $filter = $this->createFilter(['ulid' => null]);
        $context = ['filters' => []];

        $this->builder->expects($this->never())->method('match');

        $filter->apply(
            $this->builder,
            Customer::class,
            null,
            $context
        );
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
        $ulidString = (string) $this->faker->ulid();
        $context = $this->buildContext([
            'customer.ulid' => ['lt' => $ulidString],
        ]);

        $this->setupSingleMatchExpectation();

        $filter->apply(
            $this->builder,
            Customer::class,
            null,
            $context
        );
    }

    public function testApplyWithDeeplyNestedProperty(): void
    {
        $filter = $this->createFilterWithMapping([
            'customer.address.ulid' => null,
        ]);
        $ulidString = (string) $this->faker->ulid();
        $context = $this->buildContext([
            'customer.address.ulid' => ['lt' => $ulidString],
        ]);

        $this->setupSingleMatchExpectation();

        $filter->apply(
            $this->builder,
            Customer::class,
            null,
            $context
        );
    }

    public function testApplyWithInvalidOperator(): void
    {
        $filter = $this->createFilter(['ulid' => null]);
        $context = $this->buildContext([
            'ulid' => ['invalid' => (string) $this->faker->ulid()],
        ]);

        $this->builder->expects($this->never())->method('match');

        $filter->apply(
            $this->builder,
            Customer::class,
            null,
            $context
        );
    }

    public function testApplyWithNullValue(): void
    {
        $filter = $this->createFilter(['ulid' => null]);
        $context = $this->buildContext(['ulid' => ['lt' => null]]);

        $this->builder->expects($this->never())->method('match');

        $filter->apply(
            $this->builder,
            Customer::class,
            null,
            $context
        );
    }

    public function testApplyWithEmptyValue(): void
    {
        $filter = $this->createFilter(['ulid' => null]);
        $context = $this->buildContext(['ulid' => ['lt' => '']]);

        $this->builder->expects($this->never())->method('match');

        $filter->apply(
            $this->builder,
            Customer::class,
            null,
            $context
        );
    }

    public function testApplyWithInvalidUlidFormat(): void
    {
        $filter = $this->createFilter(['ulid' => null]);
        $context = $this
            ->buildContext(['ulid' => ['lt' => $this->faker->word()]]);

        $this->builder->expects($this->never())->method('match');

        $filter->apply(
            $this->builder,
            Customer::class,
            null,
            $context
        );
    }

    public function testApplyWithOperation(): void
    {
        $filter = $this->createFilterWithMapping(['ulid' => null]);
        $ulidString = (string) $this->faker->ulid();
        $context = $this->buildContext([
            'ulid' => ['lt' => $ulidString],
        ]);
        $operation = $this->createMock(Operation::class);

        $this->setupSingleMatchExpectation();

        $filter->apply(
            $this->builder,
            Customer::class,
            $operation,
            $context
        );
    }

    public function testApplyWithMultipleProperties(): void
    {
        $filter = $this->createFilterWithMapping(
            ['ulid' => null, 'customer.ulid' => null]
        );
        $ulidString = (string) $this->faker->ulid();
        $context = $this->buildContext([
            'ulid' => ['lt' => $ulidString],
            'customer.ulid' => ['lt' => $ulidString],
        ]);

        $this->setupMultiplePropertiesExpectations();

        $filter->apply($this->builder, Customer::class, null, $context);
    }

    /**
     * @param array<string, string|bool> $description
     */
    private function assertDescriptionContent(array $description): void
    {
        $this->assertEquals('ulid', $description['property']);
        $this->assertEquals('string', $description['type']);
        $this->assertFalse($description['required']);
    }

    /**
     * @param array<string, mixed> $properties
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
     * @param array<string, mixed> $properties
     */
    private function createFilterWithMapping(array $properties): UlidRangeFilter
    {
        return new UlidRangeFilter(
            $this->managerRegistry,
            $this->logger,
            $properties,
            $this->nameConverter
        );
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    private function buildContext(array $filters): array
    {
        return ['filters' => $filters];
    }

    private function setupSingleMatchExpectation(): void
    {
        $this->builder->expects($this->once())
            ->method('match')
            ->willReturn($this->matchStage);
    }

    /**
     * @return array{UlidRangeFilter, array<string, mixed>}
     */
    private function setupMultipleValuesTest(): array
    {
        $filter = $this->createFilterWithMapping(['ulid' => null]);
        $context = $this->buildContext([
            'ulid' => [
                'lt' => (string) $this->faker->ulid(),
                'gt' => (string) $this->faker->ulid(),
            ],
        ]);

        return [$filter, $context];
    }

    private function setupMultipleValuesExpectations(): void
    {
        $this->builder->expects($this->exactly(2))
            ->method('match')
            ->willReturn($this->matchStage);
    }

    /**
     * @return array{UlidRangeFilter, array<string, mixed>}
     */
    private function setupAllOperatorsTest(): array
    {
        $filter = $this->createFilterWithMapping(['ulid' => null]);
        $context = $this->buildContext([
            'ulid' => [
                'lt' => (string) $this->faker->ulid(),
                'lte' => (string) $this->faker->ulid(),
                'gt' => (string) $this->faker->ulid(),
                'gte' => (string) $this->faker->ulid(),
                'between' => sprintf(
                    '%s..%s',
                    (string) $this->faker->ulid(),
                    (string) $this->faker->ulid()
                ),
            ],
        ]);

        return [$filter, $context];
    }

    private function setupAllOperatorsExpectations(): void
    {
        $this->builder->expects($this->exactly(5))
            ->method('match')
            ->willReturn($this->matchStage);
    }

    private function setupMultiplePropertiesExpectations(): void
    {
        $this->builder->expects($this->exactly(2))
            ->method('match')
            ->willReturn($this->matchStage);
    }
}
