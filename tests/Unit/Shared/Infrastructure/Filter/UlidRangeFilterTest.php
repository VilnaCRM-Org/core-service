<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Filter;

use ApiPlatform\Metadata\Operation;
use App\Customer\Domain\Entity\Customer;
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
    /**
     * @var array{
     *     0: UlidRangeFilter,
     *     1: array<string, array<string, string>>,
     *     2: \App\Shared\Domain\ValueObject\Ulid,
     *     3: \App\Shared\Domain\ValueObject\Ulid
     * }
     */
    private array $multipleValuesTestSetup;
    /**
     * @var array{
     *     0: UlidRangeFilter,
     *     1: array<string, array<string, string>>,
     *     2: \App\Shared\Domain\ValueObject\Ulid,
     *     3: \App\Shared\Domain\ValueObject\Ulid,
     *     4: \App\Shared\Domain\ValueObject\Ulid,
     *     5: \App\Shared\Domain\ValueObject\Ulid,
     *     6: \App\Shared\Domain\ValueObject\Ulid,
     *     7: \App\Shared\Domain\ValueObject\Ulid
     * }
     */
    private array $allOperatorsTestSetup;

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
        $ulidValue = new Ulid($ulidString);
        $context = $this->buildContext([
            'ulid' => ['lt' => $ulidString],
        ]);

        $this->setupSingleMatchExpectationWithUlid('ulid', 'lt', $ulidValue);

        $filter->apply($this->builder, Customer::class, null, $context);
    }

    public function testApplyWithMultipleValues(): void
    {
        $this->multipleValuesTestSetup = $this->setupMultipleValuesTest();
        [$filter, $context] = $this->multipleValuesTestSetup;
        $this->setupMultipleValuesExpectations();

        $filter->apply($this->builder, Customer::class, null, $context);
    }

    public function testApplyWithAllOperators(): void
    {
        $this->allOperatorsTestSetup = $this->setupAllOperatorsTest();
        [$filter, $context] = $this->allOperatorsTestSetup;
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
            ->buildContext(['email' => ['lt' => 'test@example.com']]);

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
        $ulidValue = new Ulid($ulidString);
        $context = $this->buildContext([
            'customer.ulid' => ['lt' => $ulidString],
        ]);

        $this->setupSingleMatchExpectationWithUlid(
            'customer.ulid',
            'lt',
            $ulidValue
        );

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
        $ulidValue = new Ulid($ulidString);
        $context = $this->buildContext([
            'customer.address.ulid' => ['lt' => $ulidString],
        ]);

        $this->setupSingleMatchExpectationWithUlid(
            'customer.address.ulid',
            'lt',
            $ulidValue
        );

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
            'ulid' => ['invalid' => '01JKX8XGHVDZ46MWYMZT94YER4'],
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
        $context = $this->buildContext(['ulid' => ['lt' => 'invalid-ulid']]);

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
        $ulidValue = new Ulid($ulidString);
        $context = $this->buildContext([
            'ulid' => ['lt' => $ulidString],
        ]);
        $operation = $this->createMock(Operation::class);

        $this->setupSingleMatchExpectationWithUlid(
            'ulid',
            'lt',
            $ulidValue
        );

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
        $ulidValue = new Ulid($ulidString);
        $context = $this->buildContext([
            'ulid' => ['lt' => $ulidString],
            'customer.ulid' => ['lt' => $ulidString],
        ]);

        $this->setupMultiplePropertiesExpectations($ulidValue);

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
        $this->assertEquals(
            'Filter on the ulid property using the lt operator',
            $description['description']
        );
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

        $filter->method('denormalizePropertyName')
            ->willReturnArgument(0);
        $filter->method('isPropertyEnabled')->willReturn(true);
        $filter->method('isPropertyMapped')->willReturn(true);

        return $filter;
    }

    /**
     * @return array{0: UlidRangeFilter, 1: array<string, array<string, string>>}
     */
    private function setupMultipleValuesTest(): array
    {
        $filter = $this->createFilterWithMapping(['ulid' => null]);
        $ulidString1 = (string) $this->faker->ulid();
        $ulidString2 = (string) $this->faker->ulid();
        $ulidValue1 = new Ulid($ulidString1);
        $ulidValue2 = new Ulid($ulidString2);
        $value = [
            'lt' => $ulidString1,
            'gt' => $ulidString2,
        ];
        $context = $this->buildContext(['ulid' => $value]);

        return [$filter, $context, $ulidValue1, $ulidValue2];
    }

    private function setupMultipleValuesExpectations(): void
    {
        [,, $ulidValue1, $ulidValue2] = $this->multipleValuesTestSetup;

        $this->setupMatchExpectations(2, 'ulid');

        $this->matchStage->expects($this->once())
            ->method('lt')
            ->with($this->equalTo($ulidValue1))
            ->willReturnSelf();

        $this->matchStage->expects($this->once())
            ->method('gt')
            ->with($this->equalTo($ulidValue2))
            ->willReturnSelf();
    }

    /**
     * @return array{0: UlidRangeFilter, 1: array<string, array<string, string>>}
     */
    private function setupAllOperatorsTest(): array
    {
        $filter = $this->createFilterWithMapping(['ulid' => null]);

        [$ulidStrings, $ulidValues] = $this->generateUlidTestValues();
        $context = $this->buildContextForAllOperators($ulidStrings);

        return [
            $filter,
            $context,
            ...$ulidValues,
        ];
    }

    /**
     * @return array{0: array<string>, 1: array<Ulid>}
     */
    private function generateUlidTestValues(): array
    {
        $ulidStrings = [];
        $ulidValues = [];

        for ($i = 1; $i <= 6; $i++) {
            $ulidString = (string) $this->faker->ulid();
            $ulidStrings[$i] = $ulidString;
            $ulidValues[$i] = new Ulid($ulidString);
        }

        return [$ulidStrings, $ulidValues];
    }

    /**
     * @param array<int, string> $ulidStrings
     *
     * @return array<string, array<string, string>>
     */
    private function buildContextForAllOperators(array $ulidStrings): array
    {
        $value = [
            'lt' => $ulidStrings[1],
            'lte' => $ulidStrings[2],
            'gt' => $ulidStrings[3],
            'gte' => $ulidStrings[4],
            'between' => $ulidStrings[5] . '..' . $ulidStrings[6],
        ];

        return $this->buildContext(['ulid' => $value]);
    }

    private function setupAllOperatorsExpectations(): void
    {
        $testSetup = $this->allOperatorsTestSetup;
        $ulidValue1 = $testSetup[2];
        $ulidValue2 = $testSetup[3];
        $ulidValue3 = $testSetup[4];
        $ulidValue4 = $testSetup[5];
        $ulidValue5 = $testSetup[6];
        $ulidValue6 = $testSetup[7];

        $this->setupMatchExpectations(5, 'ulid');
        $this->setupLtGtExpectationsWithUlid($ulidValue1, $ulidValue3);
        $this->setupLteExpectationsWithUlid($ulidValue2, $ulidValue6);
        $this->setupGteExpectationsWithUlid($ulidValue4, $ulidValue5);
    }

    private function setupLtGtExpectationsWithUlid(
        Ulid $ltValue,
        Ulid $gtValue
    ): void {
        $this->matchStage->expects($this->once())
            ->method('lt')
            ->with($this->equalTo($ltValue))
            ->willReturnSelf();

        $this->matchStage->expects($this->once())
            ->method('gt')
            ->with($this->equalTo($gtValue))
            ->willReturnSelf();
    }

    private function setupLteExpectationsWithUlid(
        Ulid $lteValue,
        Ulid $betweenEndValue
    ): void {
        $this->matchStage->expects($this->exactly(2))
            ->method('lte')
            ->withConsecutive(
                [$this->equalTo($lteValue)],
                [$this->equalTo($betweenEndValue)]
            )
            ->willReturnSelf();
    }

    private function setupGteExpectationsWithUlid(
        Ulid $gteValue,
        Ulid $betweenStartValue
    ): void {
        $this->matchStage->expects($this->exactly(2))
            ->method('gte')
            ->withConsecutive(
                [$this->equalTo($gteValue)],
                [$this->equalTo($betweenStartValue)]
            )
            ->willReturnSelf();
    }

    private function setupMultiplePropertiesExpectations(
        ?Ulid $ulidValue = null
    ): void {
        if ($ulidValue === null) {
            $ulidString = (string) $this->faker->ulid();
            $ulidValue = new Ulid($ulidString);
        }

        $this->builder->expects($this->exactly(2))
            ->method('match')
            ->willReturn($this->matchStage);

        $this->matchStage->expects($this->exactly(2))
            ->method('field')
            ->withConsecutive(['ulid'], ['customer.ulid'])
            ->willReturnSelf();

        $this->matchStage->expects($this->exactly(2))
            ->method('lt')
            ->with($this->equalTo($ulidValue))
            ->willReturnSelf();
    }

    /**
     * @param array<string, array<string, string|null>|string> $filters
     *
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

    private function setupSingleMatchExpectationWithUlid(
        string $field,
        string $operator,
        Ulid $value
    ): void {
        $this->setupMatchExpectations(1, $field);

        $this->matchStage->expects($this->once())
            ->method($operator)
            ->with($this->equalTo($value))
            ->willReturnSelf();
    }
}
