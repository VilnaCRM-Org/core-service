<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use App\Shared\Application\OpenApi\Processor\HydraCollectionSchemaFixer;
use App\Shared\Application\OpenApi\Processor\HydraSchemaNormalizer;
use App\Shared\Application\OpenApi\Processor\HydraViewExampleUpdater;
use App\Tests\Unit\UnitTestCase;
use ArrayObject;

final class HydraCollectionSchemaFixerTest extends UnitTestCase
{
    public function testApplyReturnsOriginalSchemasWhenUpdatedIsNull(): void
    {
        $schemaNormalizer = $this->createMock(HydraSchemaNormalizer::class);
        $schemaNormalizer->expects(self::once())
            ->method('normalize')
            ->willReturn([
                'key' => 'value',
                'HydraCollectionBaseSchema' => ['allOf' => null],
            ]);

        $viewExampleUpdater = $this->createMock(HydraViewExampleUpdater::class);
        $viewExampleUpdater->expects(self::exactly(2))
            ->method('update')
            ->willReturnCallback(
                static fn (array $schema): ?array => $schema === ['allOf' => null] ? null : null
            );

        $fixer = new HydraCollectionSchemaFixer($schemaNormalizer, $viewExampleUpdater);
        $schemas = new ArrayObject(['key' => 'value']);

        $result = $fixer->apply($schemas);

        self::assertSame($schemas, $result);
        self::assertSame(['key' => 'value'], $result->getArrayCopy());
    }

    public function testApplyReturnsUpdatedSchemasWhenBothReturnValues(): void
    {
        $schemaNormalizer = $this->createMock(HydraSchemaNormalizer::class);
        $schemaNormalizer->expects(self::once())
            ->method('normalize')
            ->willReturn([
                'key' => 'value',
                'HydraCollectionBaseSchema' => ['allOf' => []],
            ]);

        $viewExampleUpdater = $this->createMock(HydraViewExampleUpdater::class);
        $viewExampleUpdater->expects(self::exactly(2))
            ->method('update')
            ->willReturnCallback(
                static fn (array $schema): ?array => $schema === ['allOf' => []]
                    ? ['allOf' => [], 'updated' => true]
                    : null
            );

        $fixer = new HydraCollectionSchemaFixer($schemaNormalizer, $viewExampleUpdater);
        $schemas = new ArrayObject(['key' => 'value']);

        $result = $fixer->apply($schemas);

        self::assertInstanceOf(ArrayObject::class, $result);
        self::assertArrayHasKey('HydraCollectionBaseSchema', $result);
        self::assertInstanceOf(ArrayObject::class, $result['HydraCollectionBaseSchema']);
        self::assertSame(
            ['allOf' => [], 'updated' => true],
            $result['HydraCollectionBaseSchema']->getArrayCopy()
        );
        self::assertArrayHasKey('key', $result);
        self::assertSame('value', $result['key']);
    }
}
