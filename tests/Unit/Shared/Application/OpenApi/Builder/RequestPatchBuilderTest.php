<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Builder;

use ApiPlatform\OpenApi\Model\RequestBody;
use App\Shared\Application\OpenApi\Builder\ContextBuilder;
use App\Shared\Application\OpenApi\Builder\RequestPatchBuilder;
use App\Tests\Unit\UnitTestCase;

final class RequestPatchBuilderTest extends UnitTestCase
{
    public function testBuild(): void
    {
        $contextBuilderMock = $this->createMock(ContextBuilder::class);

        $params = [];
        $expectedContent = new \ArrayObject([
            'application/merge-patch+json' => [
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

    public function testBuildRequiredCreatesRequiredRequestBody(): void
    {
        $contextBuilderMock = $this->createMock(ContextBuilder::class);
        $params = [];

        $contextBuilderMock->expects($this->once())
            ->method('build')
            ->with($params)
            ->willReturn(new \ArrayObject([]));

        $requestPatchBuilder = new RequestPatchBuilder($contextBuilderMock);
        $requestBody = $requestPatchBuilder->buildRequired($params);

        $this->assertInstanceOf(RequestBody::class, $requestBody);
    }

    public function testBuildOptionalCreatesOptionalRequestBody(): void
    {
        $contextBuilderMock = $this->createMock(ContextBuilder::class);
        $params = [];

        $contextBuilderMock->expects($this->once())
            ->method('build')
            ->with($params)
            ->willReturn(new \ArrayObject([]));

        $requestPatchBuilder = new RequestPatchBuilder($contextBuilderMock);
        $requestBody = $requestPatchBuilder->buildOptional($params);

        $this->assertInstanceOf(RequestBody::class, $requestBody);
    }
}
