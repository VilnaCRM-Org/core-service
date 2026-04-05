<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use App\Shared\Application\OpenApi\Processor\SchemaNormalizer;
use App\Tests\Unit\UnitTestCase;
use ArrayObject;

final class SchemaNormalizerTest extends UnitTestCase
{
    /**
     * @return iterable<string, array{0: mixed}>
     */
    public static function nonArrayInputsProvider(): iterable
    {
        yield 'null' => [null];
        yield 'bool false' => [false];
        yield 'bool true' => [true];
        yield 'int' => [123];
        yield 'float' => [1.5];
        yield 'string' => ['foo'];
        yield 'object' => [new \stdClass()];
    }

    public function testNormalizeReturnsArrayCopyForArrayObject(): void
    {
        $schema = new ArrayObject(['foo' => 'bar']);

        $this->assertSame(['foo' => 'bar'], SchemaNormalizer::normalize($schema));
    }

    public function testNormalizeReturnsArrayForArrayInput(): void
    {
        $schema = ['foo' => 'bar'];

        $this->assertSame(['foo' => 'bar'], SchemaNormalizer::normalize($schema));
    }

    /**
     * @dataProvider nonArrayInputsProvider
     */
    public function testNormalizeReturnsEmptyArrayForNonArrayInput(mixed $input): void
    {
        $this->assertSame([], SchemaNormalizer::normalize($input));
    }
}
