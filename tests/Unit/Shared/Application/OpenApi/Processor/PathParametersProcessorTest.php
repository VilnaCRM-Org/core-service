<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Processor\PathParametersProcessor;
use App\Tests\Unit\UnitTestCase;

final class PathParametersProcessorTest extends UnitTestCase
{
    private PathParametersProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->processor = new PathParametersProcessor();
    }

    public function testProcessWithEmptyPaths(): void
    {
        $openApi = new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            new Paths()
        );

        $result = $this->processor->process($openApi);

        $this->assertCount(0, $result->getPaths()->getPaths());
    }

    public function testProcessWithPathItemWithoutOperations(): void
    {
        $pathItem = new PathItem();
        $paths = new Paths();
        $paths->addPath('/test', $pathItem);

        $openApi = new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            $paths
        );

        $result = $this->processor->process($openApi);

        $resultPath = $result->getPaths()->getPath('/test');
        $this->assertNull($resultPath->getGet());
        $this->assertNull($resultPath->getPost());
        $this->assertNull($resultPath->getPut());
        $this->assertNull($resultPath->getPatch());
        $this->assertNull($resultPath->getDelete());
    }

    public function testProcessWithPathParameters(): void
    {
        $parameter = new Parameter('id', 'path', 'Resource ID');
        $operation = (new Operation('testOp', [], [], 'Test'))
            ->withParameters([$parameter]);

        $pathItem = (new PathItem())->withGet($operation);
        $paths = new Paths();
        $paths->addPath('/test/{id}', $pathItem);

        $openApi = new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            $paths
        );

        $result = $this->processor->process($openApi);

        $resultPath = $result->getPaths()->getPath('/test/{id}')->getGet();
        $this->assertCount(1, $resultPath->getParameters());
        $this->assertEquals('id', $resultPath->getParameters()[0]->getName());
    }

    public function testProcessWithAllOperations(): void
    {
        $parameter = new Parameter('id', 'path');
        $getOp = (new Operation('get', [], [], 'Get'))->withParameters([$parameter]);
        $postOp = (new Operation('post', [], [], 'Post'))->withParameters([$parameter]);
        $putOp = (new Operation('put', [], [], 'Put'))->withParameters([$parameter]);
        $patchOp = (new Operation('patch', [], [], 'Patch'))->withParameters([$parameter]);
        $deleteOp = (new Operation('delete', [], [], 'Delete'))->withParameters([$parameter]);

        $pathItem = (new PathItem())
            ->withGet($getOp)
            ->withPost($postOp)
            ->withPut($putOp)
            ->withPatch($patchOp)
            ->withDelete($deleteOp);

        $paths = new Paths();
        $paths->addPath('/test/{id}', $pathItem);

        $openApi = new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            $paths
        );

        $result = $this->processor->process($openApi);

        $resultPath = $result->getPaths()->getPath('/test/{id}');
        $this->assertCount(1, $resultPath->getGet()->getParameters());
        $this->assertCount(1, $resultPath->getPost()->getParameters());
        $this->assertCount(1, $resultPath->getPut()->getParameters());
        $this->assertCount(1, $resultPath->getPatch()->getParameters());
        $this->assertCount(1, $resultPath->getDelete()->getParameters());
    }

    public function testProcessWithMultiplePaths(): void
    {
        $param1 = new Parameter('id', 'path');
        $param2 = new Parameter('slug', 'path');

        $operation1 = (new Operation('op1', [], [], 'Op 1'))->withParameters([$param1]);
        $operation2 = (new Operation('op2', [], [], 'Op 2'))->withParameters([$param2]);

        $pathItem1 = (new PathItem())->withGet($operation1);
        $pathItem2 = (new PathItem())->withGet($operation2);

        $paths = new Paths();
        $paths->addPath('/test/{id}', $pathItem1);
        $paths->addPath('/test/{slug}', $pathItem2);

        $openApi = new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            $paths
        );

        $result = $this->processor->process($openApi);

        $this->assertCount(2, $result->getPaths()->getPaths());
        $this->assertEquals('id', $result->getPaths()->getPath('/test/{id}')->getGet()->getParameters()[0]->getName());
        $this->assertEquals('slug', $result->getPaths()->getPath('/test/{slug}')->getGet()->getParameters()[0]->getName());
    }

    public function testProcessWithOperationWithoutParameters(): void
    {
        $operation = new Operation('test', [], [], 'Test operation');
        $pathItem = (new PathItem())->withGet($operation);
        $paths = new Paths();
        $paths->addPath('/test', $pathItem);

        $openApi = new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            $paths
        );

        $result = $this->processor->process($openApi);

        $resultPath = $result->getPaths()->getPath('/test')->getGet();
        $parameters = $resultPath->getParameters();
        $this->assertTrue($parameters === null || is_array($parameters));
    }
}
