<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use App\Shared\Application\OpenApi\Processor\IriReferencePropertyTransformer;
use App\Tests\Unit\UnitTestCase;

final class IriReferencePropertyTransformerTest extends UnitTestCase
{
    private IriReferencePropertyTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transformer = new IriReferencePropertyTransformer();
    }

    public function testTransformReturnsSchemaWhenTypeIsNotIriReference(): void
    {
        $schema = ['type' => 'string', 'description' => 'no changes'];

        self::assertSame($schema, $this->transformer->transform($schema));
    }

    public function testTransformNormalizesIriReferenceType(): void
    {
        $schema = ['type' => 'iri-reference', 'description' => 'Customer relation'];

        $result = $this->transformer->transform($schema);

        self::assertSame(
            [
                'type' => 'string',
                'description' => 'Customer relation',
                'format' => 'iri-reference',
            ],
            $result
        );
    }
}
