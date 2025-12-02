<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Sanitizer;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Cleaner\PathParameterCleanerInterface;
use App\Shared\Application\OpenApi\Sanitizer\PathParametersSanitizer;
use App\Tests\Unit\UnitTestCase;

final class PathParametersSanitizerTest extends UnitTestCase
{
    private PathParametersSanitizer $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sanitizer = new PathParametersSanitizer();
    }

    public function testSanitizeWithEmptyPaths(): void
    {
        $paths = new Paths();
        $openApi = new OpenApi(
            new \ApiPlatform\OpenApi\Model\Info('Test', '1.0.0'),
            [],
            $paths
        );

        $result = $this->sanitizer->sanitize($openApi);

        $this->assertCount(0, $result->getPaths()->getPaths());
    }

    public function testSanitizeWithPathItemWithoutOperations(): void
    {
        $pathItem = new PathItem();
        $paths = new Paths();
        $paths->addPath('/test', $pathItem);

        $openApi = new OpenApi(
            new \ApiPlatform\OpenApi\Model\Info('Test', '1.0.0'),
            [],
            $paths
        );

        $result = $this->sanitizer->sanitize($openApi);

        $this->assertCount(1, $result->getPaths()->getPaths());
        $this->assertNull($result->getPaths()->getPath('/test')->getGet());
    }

    public function testSanitizeWithOperationWithPathParameter(): void
    {
        $parameter = new Parameter('id', 'path', 'User ID');
        $operation = (new Operation('testOperation', [], [], 'Test operation'))
            ->withParameters([$parameter]);
        $pathItem = (new PathItem())->withGet($operation);
        $openApi = $this->createOpenApiWithPath('/test/{id}', $pathItem);

        $result = $this->sanitizer->sanitize($openApi);

        $params = $result->getPaths()->getPath('/test/{id}')->getGet()->getParameters();
        $this->assertCount(1, $params);
        $this->assertEquals('id', $params[0]->getName());
        $this->assertEquals('path', $params[0]->getIn());
    }

    public function testSanitizeWithMultipleParameterTypes(): void
    {
        $parameters = [
            new Parameter('id', 'path', 'User ID'),
            new Parameter('page', 'query', 'Page number'),
            new Parameter('Authorization', 'header', 'Auth token'),
        ];
        $operation = (new Operation('testOperation', [], [], 'Test operation'))
            ->withParameters($parameters);
        $pathItem = (new PathItem())->withGet($operation);
        $openApi = $this->createOpenApiWithPath('/test/{id}', $pathItem);

        $result = $this->sanitizer->sanitize($openApi);

        $params = $result->getPaths()->getPath('/test/{id}')->getGet()->getParameters();
        $this->assertCount(3, $params);
        $this->assertEquals('id', $params[0]->getName());
        $this->assertEquals('page', $params[1]->getName());
        $this->assertEquals('Authorization', $params[2]->getName());
    }

    public function testSanitizeWithAllOperationTypes(): void
    {
        $parameter = new Parameter('id', 'path', 'ID');
        $pathItem = $this->createPathItemWithAllOperations($parameter);
        $openApi = $this->createOpenApiWithPath('/test/{id}', $pathItem);

        $result = $this->sanitizer->sanitize($openApi);
        $resultPath = $result->getPaths()->getPath('/test/{id}');

        $this->assertCount(1, $resultPath->getGet()->getParameters());
        $this->assertCount(1, $resultPath->getPost()->getParameters());
        $this->assertCount(1, $resultPath->getPut()->getParameters());
        $this->assertCount(1, $resultPath->getPatch()->getParameters());
        $this->assertCount(1, $resultPath->getDelete()->getParameters());
    }

    public function testSanitizeWithCustomPathParameterCleaner(): void
    {
        $mockCleaner = $this->createMock(PathParameterCleanerInterface::class);
        $mockCleaner->expects($this->once())
            ->method('clean')
            ->willReturnCallback(static fn ($param) => $param);

        $sanitizer = new PathParametersSanitizer($mockCleaner);

        $parameter = new Parameter('id', 'path', 'ID');
        $operation = (new Operation('test', [], [], 'Test'))->withParameters([$parameter]);
        $pathItem = (new PathItem())->withGet($operation);
        $paths = new Paths();
        $paths->addPath('/test/{id}', $pathItem);

        $openApi = new OpenApi(
            new \ApiPlatform\OpenApi\Model\Info('Test', '1.0.0'),
            [],
            $paths
        );

        $result = $sanitizer->sanitize($openApi);

        $this->assertInstanceOf(OpenApi::class, $result);
    }

    public function testSanitizeSkipsOperationsWithoutParameterArray(): void
    {
        $mockCleaner = $this->createMock(PathParameterCleanerInterface::class);
        $mockCleaner->expects($this->never())->method('clean');

        $sanitizer = new PathParametersSanitizer($mockCleaner);
        $operation = new Operation('test');
        $pathItem = (new PathItem())->withGet($operation);
        $openApi = $this->createOpenApiWithPath('/test', $pathItem);

        $result = $sanitizer->sanitize($openApi);

        self::assertSame($operation, $result->getPaths()->getPath('/test')->getGet());
    }

    public function testSanitizeReturnsOpenApiInstance(): void
    {
        $paths = new Paths();
        $openApi = new OpenApi(
            new \ApiPlatform\OpenApi\Model\Info('Test', '1.0.0'),
            [],
            $paths
        );

        $result = $this->sanitizer->sanitize($openApi);

        $this->assertInstanceOf(OpenApi::class, $result);
    }

    public function testSanitizeWithMultiplePaths(): void
    {
        $param1 = new Parameter('id', 'path', 'User ID');
        $param2 = new Parameter('postId', 'path', 'Post ID');

        $operation1 = (new Operation('test', [], [], 'Test'))->withParameters([$param1]);
        $operation2 = (new Operation('test', [], [], 'Test'))->withParameters([$param2]);

        $pathItem1 = (new PathItem())->withGet($operation1);
        $pathItem2 = (new PathItem())->withGet($operation2);

        $paths = new Paths();
        $paths->addPath('/users/{id}', $pathItem1);
        $paths->addPath('/posts/{postId}', $pathItem2);

        $openApi = new OpenApi(
            new \ApiPlatform\OpenApi\Model\Info('Test', '1.0.0'),
            [],
            $paths
        );

        $result = $this->sanitizer->sanitize($openApi);

        $this->assertCount(2, $result->getPaths()->getPaths());
        $usersPath = $result->getPaths()->getPath('/users/{id}');
        $postsPath = $result->getPaths()->getPath('/posts/{postId}');

        $this->assertEquals('id', $usersPath->getGet()->getParameters()[0]->getName());
        $this->assertEquals('postId', $postsPath->getGet()->getParameters()[0]->getName());
    }

    private function createPathItemWithAllOperations(Parameter $parameter): PathItem
    {
        return (new PathItem())
            ->withGet((new Operation('get', [], [], 'Get'))->withParameters([$parameter]))
            ->withPost((new Operation('post', [], [], 'Post'))->withParameters([$parameter]))
            ->withPut((new Operation('put', [], [], 'Put'))->withParameters([$parameter]))
            ->withPatch((new Operation('patch', [], [], 'Patch'))->withParameters([$parameter]))
            ->withDelete((new Operation('delete', [], [], 'Delete'))->withParameters([$parameter]));
    }

    private function createOpenApiWithPath(string $path, PathItem $pathItem): OpenApi
    {
        $paths = new Paths();
        $paths->addPath($path, $pathItem);

        return new OpenApi(
            new \ApiPlatform\OpenApi\Model\Info('Test', '1.0.0'),
            [],
            $paths
        );
    }
}
