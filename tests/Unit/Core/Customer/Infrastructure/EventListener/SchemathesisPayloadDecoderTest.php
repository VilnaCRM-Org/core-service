<?php

declare(strict_types=1);

namespace App\Tests\Unit\Core\Customer\Infrastructure\EventListener;

use App\Core\Customer\Infrastructure\EventListener\SchemathesisPayloadDecoder;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

final class SchemathesisPayloadDecoderTest extends UnitTestCase
{
    private SchemathesisPayloadDecoder $decoder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->decoder = new SchemathesisPayloadDecoder();
    }

    public function testDecodeValidJson(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getContent')->willReturn('{"email":"test@example.com"}');

        $result = $this->decoder->decode($request);

        $this->assertEquals(['email' => 'test@example.com'], $result);
    }

    public function testDecodeEmptyContent(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getContent')->willReturn('');

        $result = $this->decoder->decode($request);

        $this->assertEquals([], $result);
    }

    public function testDecodeInvalidJson(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getContent')->willReturn('invalid json');

        $result = $this->decoder->decode($request);

        $this->assertEquals([], $result);
    }

    public function testDecodeNullEmail(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getContent')->willReturn('{"email":null}');

        $result = $this->decoder->decode($request);

        $this->assertEquals(['email' => null], $result);
    }
}
