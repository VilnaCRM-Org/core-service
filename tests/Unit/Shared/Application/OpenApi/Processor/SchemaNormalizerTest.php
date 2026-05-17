<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use App\Shared\Application\OpenApi\Processor\SchemaNormalizer;
use App\Tests\Unit\UnitTestCase;
use ArrayObject;

final class SchemaNormalizerTest extends UnitTestCase
{
    public function testNormalizeReturnsArrayCopyForArrayObject(): void
    {
        $schema = new ArrayObject(['foo' => 'bar']);

        $this->assertSame(['foo' => 'bar'], (new SchemaNormalizer())->normalize($schema));
    }

    public function testNormalizeReturnsArrayForArrayInput(): void
    {
        $schema = ['foo' => 'bar'];

        $this->assertSame(['foo' => 'bar'], (new SchemaNormalizer())->normalize($schema));
    }

    public function testNormalizeReturnsEmptyArrayForNonArrayInput(): void
    {
        foreach ($this->nonArrayInputs() as $input) {
            $this->assertSame([], (new SchemaNormalizer())->normalize($input));
        }
    }

    /**
     * @return iterable<string, mixed>
     */
    private function nonArrayInputs(): iterable
    {
        yield 'null' => null;
        yield 'bool false' => false;
        yield 'bool true' => true;
        yield 'int' => 123;
        yield 'float' => 1.5;
        yield 'string' => 'foo';
        yield 'object' => new \stdClass();
    }
}
