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

        $this->assertSame(['foo' => 'bar'], SchemaNormalizer::normalize($schema));
    }

    public function testNormalizeReturnsArrayForArrayInput(): void
    {
        $schema = ['foo' => 'bar'];

        $this->assertSame(['foo' => 'bar'], SchemaNormalizer::normalize($schema));
    }

    public function testNormalizeReturnsEmptyArrayForNonArrayInput(): void
    {
        $this->assertSame([], SchemaNormalizer::normalize(null));
    }
}
