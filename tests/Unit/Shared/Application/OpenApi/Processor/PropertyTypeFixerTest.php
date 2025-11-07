<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use App\Shared\Application\OpenApi\Processor\PropertyTypeFixer;
use App\Tests\Unit\UnitTestCase;

final class PropertyTypeFixerTest extends UnitTestCase
{
    private PropertyTypeFixer $fixer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixer = new PropertyTypeFixer();
    }

    public function testFixConvertsTypeToStringWithFormat(): void
    {
        $propSchema = ['type' => 'iri-reference', 'description' => 'A relation'];

        $result = $this->fixer->fix($propSchema);

        $this->assertEquals('string', $result['type']);
        $this->assertEquals('iri-reference', $result['format']);
        $this->assertEquals('A relation', $result['description']);
    }

    public function testFixPreservesExistingProperties(): void
    {
        $propSchema = [
            'type' => 'iri-reference',
            'description' => 'A relation field',
            'example' => '/api/customers/123',
            'nullable' => true,
        ];

        $result = $this->fixer->fix($propSchema);

        $this->assertEquals('string', $result['type']);
        $this->assertEquals('iri-reference', $result['format']);
        $this->assertEquals('A relation field', $result['description']);
        $this->assertEquals('/api/customers/123', $result['example']);
        $this->assertTrue($result['nullable']);
    }

    public function testNeedsFixReturnsTrueForIriReference(): void
    {
        $propSchema = ['type' => 'iri-reference'];

        $this->assertTrue($this->fixer->needsFix($propSchema));
    }

    public function testNeedsFixReturnsFalseForString(): void
    {
        $propSchema = ['type' => 'string'];

        $this->assertFalse($this->fixer->needsFix($propSchema));
    }

    public function testNeedsFixReturnsFalseForInteger(): void
    {
        $propSchema = ['type' => 'integer'];

        $this->assertFalse($this->fixer->needsFix($propSchema));
    }

    public function testNeedsFixReturnsFalseWhenTypeIsMissing(): void
    {
        $propSchema = ['description' => 'No type'];

        $this->assertFalse($this->fixer->needsFix($propSchema));
    }

    public function testNeedsFixReturnsFalseForEmptyArray(): void
    {
        $propSchema = [];

        $this->assertFalse($this->fixer->needsFix($propSchema));
    }
}
