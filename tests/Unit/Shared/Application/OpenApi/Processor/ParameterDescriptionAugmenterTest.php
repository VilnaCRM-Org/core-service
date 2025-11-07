<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Processor\ParameterDescriptionAugmenter;
use App\Tests\Unit\UnitTestCase;

final class ParameterDescriptionAugmenterTest extends UnitTestCase
{
    private ParameterDescriptionAugmenter $augmenter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->augmenter = new ParameterDescriptionAugmenter();
    }

    public function testAugmentWithEmptyPaths(): void
    {
        $paths = new Paths();
        $openApi = new OpenApi(
            new \ApiPlatform\OpenApi\Model\Info('Test', '1.0.0'),
            [],
            $paths
        );

        $this->augmenter->augment($openApi);

        $this->assertCount(0, $openApi->getPaths()->getPaths());
    }

    public function testAugmentWithPathItemWithoutOperations(): void
    {
        $pathItem = new PathItem();
        $paths = new Paths();
        $paths->addPath('/test', $pathItem);

        $openApi = new OpenApi(
            new \ApiPlatform\OpenApi\Model\Info('Test', '1.0.0'),
            [],
            $paths
        );

        $this->augmenter->augment($openApi);

        $result = $openApi->getPaths()->getPath('/test');
        $this->assertNull($result->getGet());
    }

    public function testAugmentAddsDescriptionToKnownParameter(): void
    {
        $parameter = new Parameter('page', 'query');

        $operation = (new Operation(
            'testOperation',
            [],
            [],
            'Test operation'
        ))->withParameters([$parameter]);

        $pathItem = (new PathItem())->withGet($operation);
        $paths = new Paths();
        $paths->addPath('/test', $pathItem);

        $openApi = new OpenApi(
            new \ApiPlatform\OpenApi\Model\Info('Test', '1.0.0'),
            [],
            $paths
        );

        $this->augmenter->augment($openApi);

        $result = $openApi->getPaths()->getPath('/test')->getGet();
        $parameters = $result->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertEquals('Page number for pagination', $parameters[0]->getDescription());
    }

    public function testAugmentDoesNotOverrideExistingDescription(): void
    {
        $existingDescription = 'My custom page description';
        $parameter = (new Parameter('page', 'query'))->withDescription($existingDescription);
        $openApi = $this->createOpenApiWithParameters([$parameter]);

        $this->augmenter->augment($openApi);

        $parameters = $openApi->getPaths()->getPath('/test')->getGet()->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertEquals($existingDescription, $parameters[0]->getDescription());
    }

    public function testAugmentWithUnknownParameter(): void
    {
        $parameter = new Parameter('unknown_param', 'query');
        $openApi = $this->createOpenApiWithParameters([$parameter]);

        $this->augmenter->augment($openApi);

        $parameters = $openApi->getPaths()->getPath('/test')->getGet()->getParameters();
        $this->assertCount(1, $parameters);
        // Unknown parameters are left unchanged (empty string or null)
        $description = $parameters[0]->getDescription();
        $this->assertTrue($description === null || $description === '');
    }

    public function testAugmentWithMultipleParameters(): void
    {
        $parameters = [
            new Parameter('page', 'query'),
            new Parameter('itemsPerPage', 'query'),
            new Parameter('order[email]', 'query'),
            new Parameter('unknown', 'query'),
        ];
        $openApi = $this->createOpenApiWithParameters($parameters);

        $this->augmenter->augment($openApi);

        $resultParams = $openApi->getPaths()->getPath('/test')->getGet()->getParameters();
        $this->assertCount(4, $resultParams);
        $this->assertEquals('Page number for pagination', $resultParams[0]->getDescription());
        $this->assertEquals('Number of items per page', $resultParams[1]->getDescription());
        $this->assertEquals('Sort by customer email address', $resultParams[2]->getDescription());
        $description = $resultParams[3]->getDescription();
        $this->assertTrue($description === null || $description === '');
    }

    public function testAugmentWithAllOperationTypes(): void
    {
        $parameter = new Parameter('page', 'query');
        $pathItem = $this->createPathItemWithAllOperations($parameter);
        $openApi = $this->createOpenApiWithPathItem('/test', $pathItem);

        $this->augmenter->augment($openApi);

        $resultPath = $openApi->getPaths()->getPath('/test');
        $this->assertAllOperationDescriptions($resultPath, 'Page number for pagination');
    }

    public function testAugmentWithOrderParameters(): void
    {
        $parameters = [
            new Parameter('order[ulid]', 'query'),
            new Parameter('order[createdAt]', 'query'),
            new Parameter('order[email]', 'query'),
        ];
        $openApi = $this->createOpenApiWithParameters($parameters);

        $this->augmenter->augment($openApi);

        $resultParams = $openApi->getPaths()->getPath('/test')->getGet()->getParameters();
        $expected = [
            'Sort by customer unique identifier',
            'Sort by creation date',
            'Sort by customer email address',
        ];
        $this->assertParameterDescriptions($expected, $resultParams);
    }

    public function testAugmentWithFilterParameters(): void
    {
        $parameters = [
            new Parameter('email', 'query'),
            new Parameter('email[]', 'query'),
            new Parameter('phone', 'query'),
            new Parameter('confirmed', 'query'),
        ];
        $openApi = $this->createOpenApiWithParameters($parameters);

        $this->augmenter->augment($openApi);

        $resultParams = $openApi->getPaths()->getPath('/test')->getGet()->getParameters();
        $expected = [
            'Filter by customer email address (exact match)',
            'Filter by multiple customer email addresses (exact match)',
            'Filter by customer phone number (exact match)',
            'Filter by customer confirmation status (true/false)',
        ];
        $this->assertParameterDescriptions($expected, $resultParams);
    }

    public function testAugmentWithDateFilterParameters(): void
    {
        $parameters = [
            new Parameter('createdAt[before]', 'query'),
            new Parameter('createdAt[after]', 'query'),
            new Parameter('updatedAt[strictly_before]', 'query'),
        ];
        $openApi = $this->createOpenApiWithParameters($parameters);

        $this->augmenter->augment($openApi);

        $resultParams = $openApi->getPaths()->getPath('/test')->getGet()->getParameters();
        $this->assertEquals(
            'Filter customers created before this date',
            $resultParams[0]->getDescription()
        );
        $this->assertEquals(
            'Filter customers created after this date',
            $resultParams[1]->getDescription()
        );
        $this->assertEquals(
            'Filter customers updated strictly before this date',
            $resultParams[2]->getDescription()
        );
    }

    public function testAugmentWithUlidFilterParameters(): void
    {
        $parameters = [
            new Parameter('ulid[between]', 'query'),
            new Parameter('ulid[gt]', 'query'),
            new Parameter('ulid[lte]', 'query'),
        ];
        $openApi = $this->createOpenApiWithParameters($parameters);

        $this->augmenter->augment($openApi);

        $resultParams = $openApi->getPaths()->getPath('/test')->getGet()->getParameters();
        $this->assertEquals(
            'Filter by ULID range (comma-separated start and end)',
            $resultParams[0]->getDescription()
        );
        $this->assertEquals(
            'Filter by ULID greater than',
            $resultParams[1]->getDescription()
        );
        $this->assertEquals(
            'Filter by ULID less than or equal to',
            $resultParams[2]->getDescription()
        );
    }

    public function testAugmentWithMultiplePaths(): void
    {
        $parameter = new Parameter('page', 'query');
        $operation = (new Operation('test', [], [], 'Test'))->withParameters([$parameter]);

        $pathItem1 = (new PathItem())->withGet($operation);
        $pathItem2 = (new PathItem())->withPost($operation);

        $paths = new Paths();
        $paths->addPath('/path1', $pathItem1);
        $paths->addPath('/path2', $pathItem2);

        $openApi = new OpenApi(
            new \ApiPlatform\OpenApi\Model\Info('Test', '1.0.0'),
            [],
            $paths
        );

        $this->augmenter->augment($openApi);

        $path1Result = $openApi->getPaths()->getPath('/path1')->getGet();
        $path2Result = $openApi->getPaths()->getPath('/path2')->getPost();
        $expected = 'Page number for pagination';

        $this->assertEquals($expected, $path1Result->getParameters()[0]->getDescription());
        $this->assertEquals($expected, $path2Result->getParameters()[0]->getDescription());
    }

    public function testAugmentWithEmptyStringDescription(): void
    {
        $parameter = (new Parameter('page', 'query'))->withDescription('');

        $operation = (new Operation('test', [], [], 'Test'))->withParameters([$parameter]);
        $pathItem = (new PathItem())->withGet($operation);
        $paths = new Paths();
        $paths->addPath('/test', $pathItem);

        $openApi = new OpenApi(
            new \ApiPlatform\OpenApi\Model\Info('Test', '1.0.0'),
            [],
            $paths
        );

        $this->augmenter->augment($openApi);

        $result = $openApi->getPaths()->getPath('/test')->getGet();
        $resultParams = $result->getParameters();

        // Empty string should be treated as no description and be replaced
        $this->assertEquals('Page number for pagination', $resultParams[0]->getDescription());
    }

    /**
     * @param array<Parameter> $parameters
     */
    private function createOpenApiWithParameters(array $parameters, string $path = '/test'): OpenApi
    {
        $operation = (new Operation('test', [], [], 'Test'))->withParameters($parameters);
        $pathItem = (new PathItem())->withGet($operation);
        $paths = new Paths();
        $paths->addPath($path, $pathItem);

        return new OpenApi(
            new \ApiPlatform\OpenApi\Model\Info('Test', '1.0.0'),
            [],
            $paths
        );
    }

    /**
     * @param array<string> $expected
     * @param array<\ApiPlatform\OpenApi\Model\Parameter> $parameters
     */
    private function assertParameterDescriptions(array $expected, array $parameters): void
    {
        foreach ($expected as $index => $description) {
            $this->assertEquals($description, $parameters[$index]->getDescription());
        }
    }

    private function createPathItemWithAllOperations(Parameter $parameter): PathItem
    {
        $getOp = (new Operation('get', [], [], 'Get'))->withParameters([$parameter]);
        $postOp = (new Operation('post', [], [], 'Post'))->withParameters([$parameter]);
        $putOp = (new Operation('put', [], [], 'Put'))->withParameters([$parameter]);
        $patchOp = (new Operation('patch', [], [], 'Patch'))->withParameters([$parameter]);
        $deleteOp = (new Operation('delete', [], [], 'Delete'))->withParameters([$parameter]);

        return (new PathItem())
            ->withGet($getOp)
            ->withPost($postOp)
            ->withPut($putOp)
            ->withPatch($patchOp)
            ->withDelete($deleteOp);
    }

    private function createOpenApiWithPathItem(string $path, PathItem $pathItem): OpenApi
    {
        $paths = new Paths();
        $paths->addPath($path, $pathItem);

        return new OpenApi(
            new \ApiPlatform\OpenApi\Model\Info('Test', '1.0.0'),
            [],
            $paths
        );
    }

    private function assertAllOperationDescriptions(PathItem $pathItem, string $expected): void
    {
        $this->assertEquals(
            $expected,
            $pathItem->getGet()->getParameters()[0]->getDescription()
        );
        $this->assertEquals(
            $expected,
            $pathItem->getPost()->getParameters()[0]->getDescription()
        );
        $this->assertEquals(
            $expected,
            $pathItem->getPut()->getParameters()[0]->getDescription()
        );
        $this->assertEquals(
            $expected,
            $pathItem->getPatch()->getParameters()[0]->getDescription()
        );
        $this->assertEquals(
            $expected,
            $pathItem->getDelete()->getParameters()[0]->getDescription()
        );
    }
}
