<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Cleaner;

use App\Shared\Application\OpenApi\Cleaner\ArrayValueCleaner;
use App\Shared\Application\OpenApi\Cleaner\EmptyArrayCleaner;
use App\Shared\Application\OpenApi\Cleaner\ParameterCleaner;
use App\Shared\Application\OpenApi\Cleaner\ValueCleaner;
use App\Tests\Unit\UnitTestCase;

final class ArrayValueCleanerTest extends UnitTestCase
{
    private ArrayValueCleaner $processor;

    protected function setUp(): void
    {
        parent::setUp();
        $parameterCleaner = new ParameterCleaner();
        $emptyValueChecker = new EmptyArrayCleaner();
        $valueFilter = new ValueCleaner($emptyValueChecker);
        $this->processor = new ArrayValueCleaner($parameterCleaner, $valueFilter);
    }

    public function testProcessReturnsNullForEmptyRemovableArray(): void
    {
        $recursiveCleaner = static fn (array $data): array => $data;

        $result = $this->processor->clean('extensionProperties', [], $recursiveCleaner);

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

        $result = $this->processor->clean('parameters', $parameters, $recursiveCleaner);

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
            return array_map(
                static fn ($value) => is_array($value) ? ['cleaned' => true] : $value,
                $data
            );
        };

        $result = $this->processor->clean('customKey', $data, $recursiveCleaner);

        $this->assertIsArray($result);
        $this->assertEquals(['cleaned' => true], $result['nested']);
    }

    public function testProcessReturnsNullIfCleanedValueBecomesEmptyAndRemovable(): void
    {
        $data = [
            'item' => null,
        ];

        $recursiveCleaner = static fn (array $data): array => []; // Cleaner returns empty array

        $result = $this->processor->clean('responses', $data, $recursiveCleaner);

        $this->assertNull($result);
    }

    public function testProcessKeepsNonEmptyArrays(): void
    {
        $data = [
            'value' => 'test',
        ];

        $recursiveCleaner = static fn (array $data): array => $data;

        $result = $this->processor->clean('customKey', $data, $recursiveCleaner);

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

        $result = $this->processor->clean('otherKey', $data, $recursiveCleaner);

        // Should not remove allowEmptyValue since key is not 'parameters'
        $this->assertIsArray($result);
        $this->assertArrayHasKey('allowEmptyValue', $result[0]);
    }

    public function testProcessHandlesNumericKeys(): void
    {
        $data = [
            0 => ['value' => 'first'],
            1 => ['value' => 'second'],
            2 => ['value' => 'third'],
        ];

        $recursiveCleaner = static fn (array $data): array => $data;

        $result = $this->processor->clean('customKey', $data, $recursiveCleaner);

        $this->assertIsArray($result);
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey(1, $result);
        $this->assertArrayHasKey(2, $result);
        $this->assertEquals(['value' => 'first'], $result[0]);
        $this->assertEquals(['value' => 'second'], $result[1]);
        $this->assertEquals(['value' => 'third'], $result[2]);
    }

    public function testProcessHandlesMixedNumericAndStringKeys(): void
    {
        $data = [
            0 => ['value' => 'numeric'],
            'name' => ['value' => 'string'],
            1 => ['value' => 'numeric2'],
        ];

        $recursiveCleaner = static fn (array $data): array => $data;

        $result = $this->processor->clean('customKey', $data, $recursiveCleaner);

        $this->assertIsArray($result);
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey(1, $result);
        $this->assertEquals(['value' => 'numeric'], $result[0]);
        $this->assertEquals(['value' => 'string'], $result['name']);
        $this->assertEquals(['value' => 'numeric2'], $result[1]);
    }
}
