<?php

namespace App\Tests\Unit\Shared\Application\OpenApi\Builder;
use ApiPlatform\OpenApi\Model\RequestBody;
use App\Shared\Application\OpenApi\Builder\RequestPatchBuilder;
use PHPUnit\Framework\TestCase;
use App\Shared\Application\OpenApi\Builder\ContextBuilder;
class RequestPatchBuilderTest extends TestCase
{
    public function testBuild()
    {
        $contextBuilderMock = $this->createMock(ContextBuilder::class);

        $params = [];
        $expectedContent = new \ArrayObject([
            'application/ld+json' => [
                'schema' => [
                    'type' => 'object',
                    'properties' => [],
                    'required' => [],
                ],
                'example' => [],
            ],
        ]);

        $contextBuilderMock->expects($this->once())
            ->method('build')
            ->with($params)
            ->willReturn($expectedContent);

        $requestPatchBuilder = new RequestPatchBuilder($contextBuilderMock);

        $actualRequestBody = $requestPatchBuilder->build($params);

        $this->assertInstanceOf(RequestBody::class, $actualRequestBody);

        $this->assertEquals($expectedContent, $actualRequestBody->getContent());
    }
}
