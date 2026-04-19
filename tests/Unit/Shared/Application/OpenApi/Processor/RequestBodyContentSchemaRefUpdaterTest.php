<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use App\Shared\Application\OpenApi\Processor\RequestBodyContentSchemaRefUpdater;
use App\Shared\Application\OpenApi\Processor\RequestBodySchemaRefDefinitionUpdater;
use App\Tests\Unit\UnitTestCase;
use ArrayObject;

final class RequestBodyContentSchemaRefUpdaterTest extends UnitTestCase
{
    public function testUpdateContinuesPastDefinitionsThatDoNotNeedChanges(): void
    {
        $content = new ArrayObject([
            'application/problem+json' => [
                'schema' => [
                    '$ref' => '#/components/schemas/CustomerType.TypeCreate',
                ],
            ],
            'application/ld+json' => [
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'value' => ['type' => 'string'],
                    ],
                ],
            ],
        ]);

        $updated = $this->createUpdater()->update(
            $content,
            '#/components/schemas/CustomerType.TypeCreate'
        );

        self::assertInstanceOf(ArrayObject::class, $updated);
        self::assertSame(
            ['$ref' => '#/components/schemas/CustomerType.TypeCreate'],
            $updated['application/problem+json']['schema']
        );
        self::assertSame(
            ['$ref' => '#/components/schemas/CustomerType.TypeCreate'],
            $updated['application/ld+json']['schema']
        );
    }

    private function createUpdater(): RequestBodyContentSchemaRefUpdater
    {
        return new RequestBodyContentSchemaRefUpdater(
            new RequestBodySchemaRefDefinitionUpdater()
        );
    }
}
