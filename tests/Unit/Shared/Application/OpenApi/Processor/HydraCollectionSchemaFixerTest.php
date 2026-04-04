<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use App\Shared\Application\OpenApi\Processor\HydraCollectionSchemaFixer;
use App\Shared\Application\OpenApi\Processor\HydraCollectionSchemasUpdater;
use App\Shared\Application\OpenApi\Processor\HydraSchemaNormalizer;
use App\Shared\Application\OpenApi\Processor\HydraViewExampleUpdater;
use App\Tests\Unit\UnitTestCase;
use ArrayObject;

final class HydraCollectionSchemaFixerTest extends UnitTestCase
{
    public function testApplyReturnsOriginalSchemasWhenNothingChanges(): void
    {
        $schemaNormalizer = $this->createMock(HydraSchemaNormalizer::class);
        $schemaNormalizer->expects(self::once())
            ->method('normalize')
            ->willReturn(['key' => 'value']);

        $viewExampleUpdater = $this->createMock(HydraViewExampleUpdater::class);
        $viewExampleUpdater->expects(self::once())
            ->method('update')
            ->with([])
            ->willReturn(null);

        $fixer = new HydraCollectionSchemaFixer(
            $viewExampleUpdater,
            new HydraCollectionSchemasUpdater($schemaNormalizer, $viewExampleUpdater)
        );
        $schemas = new ArrayObject(['key' => 'value']);

        $result = $fixer->apply($schemas);

        self::assertSame($schemas, $result);
        self::assertSame(['key' => 'value'], $result->getArrayCopy());
    }

    public function testApplyPersistsNormalizedSchemasWhenUpdaterReturnsNull(): void
    {
        $schemaNormalizer = $this->createMock(HydraSchemaNormalizer::class);
        $schemaNormalizer->expects(self::once())
            ->method('normalize')
            ->willReturn([
                'HydraCollectionBaseSchema' => ['allOf' => []],
            ]);

        $viewExampleUpdater = $this->createMock(HydraViewExampleUpdater::class);
        $viewExampleUpdater->expects(self::once())
            ->method('update')
            ->with(['allOf' => []])
            ->willReturn(null);

        $fixer = new HydraCollectionSchemaFixer(
            $viewExampleUpdater,
            new HydraCollectionSchemasUpdater($schemaNormalizer, $viewExampleUpdater)
        );
        $schemas = new ArrayObject([
            'HydraCollectionBaseSchema' => null,
        ]);

        $result = $fixer->apply($schemas);

        self::assertNotSame($schemas, $result);
        self::assertArrayHasKey('HydraCollectionBaseSchema', $result);
        self::assertInstanceOf(ArrayObject::class, $result['HydraCollectionBaseSchema']);
        self::assertSame(['allOf' => []], $result['HydraCollectionBaseSchema']->getArrayCopy());
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

        $fixer = new HydraCollectionSchemaFixer(
            $viewExampleUpdater,
            new HydraCollectionSchemasUpdater($schemaNormalizer, $viewExampleUpdater)
        );
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

    public function testFixSchemaDelegatesToViewExampleUpdater(): void
    {
        $schemaNormalizer = $this->createMock(HydraSchemaNormalizer::class);
        $schemaNormalizer->expects(self::never())
            ->method('normalize');

        $viewExampleUpdater = $this->createMock(HydraViewExampleUpdater::class);
        $viewExampleUpdater->expects(self::once())
            ->method('update')
            ->with(['allOf' => []])
            ->willReturn(['allOf' => [], 'updated' => true]);

        $fixer = new HydraCollectionSchemaFixer(
            $viewExampleUpdater,
            new HydraCollectionSchemasUpdater($schemaNormalizer, $viewExampleUpdater)
        );

        self::assertSame(
            ['allOf' => [], 'updated' => true],
            $fixer->fixSchema(['allOf' => []])
        );
    }

    public function testApplyReturnsOriginalSchemasWhenOnlyRepresentationDiffers(): void
    {
        $schemaNormalizer = $this->createMock(HydraSchemaNormalizer::class);
        $schemaNormalizer->expects(self::once())
            ->method('normalize')
            ->willReturn([
                'HydraCollectionBaseSchema' => ['allOf' => []],
            ]);

        $viewExampleUpdater = $this->createMock(HydraViewExampleUpdater::class);
        $viewExampleUpdater->expects(self::once())
            ->method('update')
            ->with(['allOf' => []])
            ->willReturn(null);

        $fixer = new HydraCollectionSchemaFixer(
            $viewExampleUpdater,
            new HydraCollectionSchemasUpdater($schemaNormalizer, $viewExampleUpdater)
        );
        $schemas = new ArrayObject([
            'HydraCollectionBaseSchema' => new ArrayObject(['allOf' => []]),
        ]);

        self::assertSame($schemas, $fixer->apply($schemas));
    }
}
