<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Transformer;

use App\Shared\Application\Transformer\IriTransformer;
use App\Tests\Unit\UnitTestCase;

final class IriTransformerTest extends UnitTestCase
{
    private IriTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transformer = new IriTransformer();
    }

    public function testTransformFromSimpleIri(): void
    {
        $result = $this->transformer->transform('/api/customers/01HQZX123456789');

        self::assertSame('01HQZX123456789', $result);
    }

    public function testTransformFromComplexIri(): void
    {
        $result = $this->transformer->transform('/api/v2/customers/01HQZX987654321/details');

        self::assertSame('details', $result);
    }

    public function testTransformReturnsUlidAsIsWhenNotIri(): void
    {
        $ulid = '01HQZX123456789ABCDEFGH';
        $result = $this->transformer->transform($ulid);

        self::assertSame($ulid, $result);
    }

    public function testTransformReturnsNonIriQueryStringAsIs(): void
    {
        $input = 'customer-id?include=type';
        $result = $this->transformer->transform($input);

        self::assertSame($input, $result);
    }

    public function testTransformHandlesEmptyString(): void
    {
        $result = $this->transformer->transform('');

        self::assertSame('', $result);
    }

    public function testTransformHandlesIriWithTrailingSlash(): void
    {
        $result = $this->transformer->transform('/api/customers/01HQZX123456789/');

        self::assertSame('01HQZX123456789', $result);
    }

    public function testTransformHandlesRootPath(): void
    {
        $result = $this->transformer->transform('/');

        self::assertSame('', $result);
    }

    public function testTransformHandlesMultipleSlashes(): void
    {
        $result = $this->transformer->transform('/api/customer_types/01HQZX999888777');

        self::assertSame('01HQZX999888777', $result);
    }

    public function testTransformHandlesIriWithQueryString(): void
    {
        $result = $this->transformer->transform('/api/customers/01HQZX111222333?include=type');

        self::assertSame('01HQZX111222333', $result);
    }

    public function testTransformHandlesIriWithFragment(): void
    {
        $result = $this->transformer->transform('/api/customers/01HQZX444555666#section');

        self::assertSame('01HQZX444555666', $result);
    }

    public function testTransformHandlesUuidFormat(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $result = $this->transformer->transform($uuid);

        self::assertSame($uuid, $result);
    }

    public function testTransformFromIriWithUuid(): void
    {
        $iri = '/api/customers/550e8400-e29b-41d4-a716-446655440000';
        $result = $this->transformer->transform($iri);

        self::assertSame('550e8400-e29b-41d4-a716-446655440000', $result);
    }

    public function testTransformHandlesSpecialCharactersInPath(): void
    {
        $iri = '/api/customers/01HQZX_special-id.test';
        $result = $this->transformer->transform($iri);

        self::assertSame('01HQZX_special-id.test', $result);
    }
}
