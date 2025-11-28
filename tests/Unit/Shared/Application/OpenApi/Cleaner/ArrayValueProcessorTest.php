<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Cleaner;

use App\Shared\Application\OpenApi\Cleaner\ArrayValueProcessor;
use App\Shared\Application\OpenApi\Cleaner\EmptyValueChecker;
use App\Shared\Application\OpenApi\Cleaner\ParameterCleaner;
use App\Shared\Application\OpenApi\Cleaner\ValueFilter;
use App\Tests\Unit\UnitTestCase;

final class ArrayValueProcessorTest extends UnitTestCase
{
    private ArrayValueProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();
        $parameterCleaner = new ParameterCleaner();
        $emptyValueChecker = new EmptyValueChecker();
        $valueFilter = new ValueFilter($emptyValueChecker);
        $this->processor = new ArrayValueProcessor($parameterCleaner, $valueFilter);
    }

    public function testProcessReturnsNullForEmptyRemovableArray(): void
    {
        $recursiveCleaner = static fn (array $data): array => $data;

        $result = $this->processor->process('extensionProperties', [], $recursiveCleaner);

        $this->assertNull($result);
    }

    public function testProcessCleansParameters(): void
    {
        $parameters = [
            [
                'name' => 'id',
                'in' => 'path',
                'allowEmptyValue' => false,
                'allowReserved' => false,
            ],
        ];

        $recursiveCleaner = static fn (array $data): array => $data;

        $result = $this->processor->process('parameters', $parameters, $recursiveCleaner);

        $this->assertIsArray($result);
        $this->assertArrayNotHasKey('allowEmptyValue', $result[0]);
        $this->assertArrayNotHasKey('allowReserved', $result[0]);
    }

    public function testProcessAppliesRecursiveCleaner(): void
    {
        $data = [
            'nested' => ['value' => 'test'],
        ];

        $recursiveCleaner = static function (array $data): array {
            return array_map(static fn ($value) => is_array($value) ? ['cleaned' => true] : $value, $data);
        };

        $result = $this->processor->process('customKey', $data, $recursiveCleaner);

        $this->assertIsArray($result);
        $this->assertEquals(['cleaned' => true], $result['nested']);
    }

    public function testProcessReturnsNullIfCleanedValueBecomesEmptyAndRemovable(): void
    {
        $data = [
            'item' => null,
        ];

        $recursiveCleaner = static fn (array $data): array => []; // Cleaner returns empty array

        $result = $this->processor->process('responses', $data, $recursiveCleaner);

        $this->assertNull($result);
    }

    public function testProcessKeepsNonEmptyArrays(): void
    {
        $data = [
            'value' => 'test',
        ];

        $recursiveCleaner = static fn (array $data): array => $data;

        $result = $this->processor->process('customKey', $data, $recursiveCleaner);

        $this->assertIsArray($result);
        $this->assertEquals(['value' => 'test'], $result);
    }

    public function testProcessDoesNotCleanNonParameterArrays(): void
    {
        $data = [
            [
                'name' => 'test',
                'in' => 'query',
                'allowEmptyValue' => true,
            ],
        ];

        $recursiveCleaner = static fn (array $data): array => $data;

        $result = $this->processor->process('otherKey', $data, $recursiveCleaner);

        // Should not remove allowEmptyValue since key is not 'parameters'
        $this->assertIsArray($result);
        $this->assertArrayHasKey('allowEmptyValue', $result[0]);
    }
}
