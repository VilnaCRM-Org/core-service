<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Serializer;

use ApiPlatform\OpenApi\Model\Parameter;
use App\Shared\Application\OpenApi\Serializer\ParameterNormalizer;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class ParameterNormalizerTest extends UnitTestCase
{
    private NormalizerInterface $decorated;
    private ParameterNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->decorated = $this->createMock(NormalizerInterface::class);
        $this->normalizer = new ParameterNormalizer($this->decorated);
    }

    public function testNormalizePathParameterRemovesDisallowedProperties(): void
    {
        $parameter = new Parameter(
            name: 'id',
            in: 'path',
            description: 'Resource ID',
            required: true,
            schema: ['type' => 'string']
        );

        $this->decorated
            ->expects($this->once())
            ->method('normalize')
            ->with($parameter, null, [])
            ->willReturn([
                'name' => 'id',
                'in' => 'path',
                'description' => 'Resource ID',
                'required' => true,
                'allowEmptyValue' => false, // This should be removed
                'allowReserved' => false, // This should be removed
                'schema' => ['type' => 'string'],
            ]);

        $result = $this->normalizer->normalize($parameter, null, []);

        $this->assertArrayNotHasKey('allowEmptyValue', $result);
        $this->assertArrayNotHasKey('allowReserved', $result);
        $this->assertEquals('id', $result['name']);
        $this->assertEquals('path', $result['in']);
    }

    public function testNormalizeQueryParameterKeepsAllowEmptyValue(): void
    {
        $parameter = new Parameter(
            name: 'filter',
            in: 'query',
            description: 'Filter parameter'
        );

        $this->decorated
            ->expects($this->once())
            ->method('normalize')
            ->with($parameter, null, [])
            ->willReturn([
                'name' => 'filter',
                'in' => 'query',
                'description' => 'Filter parameter',
                'allowEmptyValue' => false,
            ]);

        $result = $this->normalizer->normalize($parameter, null, []);

        $this->assertArrayHasKey('allowEmptyValue', $result);
        $this->assertFalse($result['allowEmptyValue']);
    }

    public function testNormalizeNonParameterObjectPassesThrough(): void
    {
        $object = new \stdClass();

        $this->decorated
            ->expects($this->once())
            ->method('normalize')
            ->with($object, null, [])
            ->willReturn(['foo' => 'bar']);

        $result = $this->normalizer->normalize($object, null, []);

        $this->assertEquals(['foo' => 'bar'], $result);
    }

    public function testSupportsNormalization(): void
    {
        $parameter = new Parameter('test', 'path');

        $this->assertTrue($this->normalizer->supportsNormalization($parameter));
        $this->assertFalse($this->normalizer->supportsNormalization(new \stdClass()));
    }

    public function testGetSupportedTypes(): void
    {
        $types = $this->normalizer->getSupportedTypes(null);

        $this->assertArrayHasKey(Parameter::class, $types);
        $this->assertTrue($types[Parameter::class]);
    }

    public function testNormalizeParameterWithNonArrayDataPassesThrough(): void
    {
        $parameter = new Parameter('test', 'path');

        $this->decorated
            ->expects($this->once())
            ->method('normalize')
            ->with($parameter, null, [])
            ->willReturn('string-result');

        $result = $this->normalizer->normalize($parameter, null, []);

        $this->assertEquals('string-result', $result);
    }
}
