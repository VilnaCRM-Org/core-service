<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Processor\IriReferenceTypeFixer;
use App\Tests\Unit\UnitTestCase;
use ArrayObject;

final class IriReferenceTypeFixerTest extends UnitTestCase
{
    private IriReferenceTypeFixer $fixer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixer = new IriReferenceTypeFixer();
    }

    public function testFixWithEmptyPaths(): void
    {
        $paths = new Paths();
        $openApi = new OpenApi(
            new \ApiPlatform\OpenApi\Model\Info(
                'Test',
                '1.0.0'
            ),
            [],
            $paths
        );

        $this->fixer->fix($openApi);

        $this->assertCount(0, $openApi->getPaths()->getPaths());
    }

    public function testFixWithPathItemWithoutOperations(): void
    {
        $pathItem = new PathItem();
        $paths = new Paths();
        $paths->addPath('/test', $pathItem);

        $openApi = new OpenApi(
            new \ApiPlatform\OpenApi\Model\Info(
                'Test',
                '1.0.0'
            ),
            [],
            $paths
        );

        $this->fixer->fix($openApi);

        $this->assertCount(1, $openApi->getPaths()->getPaths());
    }

    public function testFixWithOperationWithoutRequestBody(): void
    {
        $operation = new Operation(
            'testOperation',
            [],
            [],
            'Test operation'
        );

        $pathItem = (new PathItem())->withPost($operation);
        $paths = new Paths();
        $paths->addPath('/test', $pathItem);

        $openApi = new OpenApi(
            new \ApiPlatform\OpenApi\Model\Info(
                'Test',
                '1.0.0'
            ),
            [],
            $paths
        );

        $this->fixer->fix($openApi);

        $fixedOperation = $openApi->getPaths()->getPath('/test')->getPost();
        $this->assertNull($fixedOperation->getRequestBody());
    }

    public function testFixWithOperationWithEmptyContent(): void
    {
        $requestBody = new RequestBody('Test request body', null);

        $operation = (new Operation(
            'testOperation',
            [],
            [],
            'Test operation'
        ))->withRequestBody($requestBody);

        $pathItem = (new PathItem())->withPost($operation);
        $paths = new Paths();
        $paths->addPath('/test', $pathItem);

        $openApi = new OpenApi(
            new \ApiPlatform\OpenApi\Model\Info(
                'Test',
                '1.0.0'
            ),
            [],
            $paths
        );

        $this->fixer->fix($openApi);

        $fixedOperation = $openApi->getPaths()->getPath('/test')->getPost();
        $this->assertNull($fixedOperation->getRequestBody()->getContent());
    }

    public function testFixConvertsIriReferenceTypeToString(): void
    {
        $properties = ['relation' => ['type' => 'iri-reference'], 'name' => ['type' => 'string']];
        $content = $this->createContentWithProperties($properties);
        $requestBody = new RequestBody('Test request body', $content);
        $operation = (new Operation('test', [], [], 'Test'))
            ->withRequestBody($requestBody);
        $openApi = $this->createOpenApiWithOperation($operation);

        $this->fixer->fix($openApi);

        $resultProps = $this->getResultProperties($openApi);
        $this->assertEquals('string', $resultProps['relation']['type']);
        $this->assertEquals('iri-reference', $resultProps['relation']['format']);
        $this->assertEquals('string', $resultProps['name']['type']);
        $this->assertArrayNotHasKey('format', $resultProps['name']);
    }

    public function testFixWithMultipleOperations(): void
    {
        $properties = ['field' => ['type' => 'iri-reference']];
        $content = $this->createContentWithProperties($properties);
        $requestBody = new RequestBody('Test request body', $content);

        $postOp = (new Operation('post', [], [], 'Post'))->withRequestBody($requestBody);
        $putOp = (new Operation('put', [], [], 'Put'))->withRequestBody($requestBody);
        $patchOp = (new Operation('patch', [], [], 'Patch'))->withRequestBody($requestBody);

        $pathItem = (new PathItem())->withPost($postOp)->withPut($putOp)->withPatch($patchOp);
        $openApi = $this->createOpenApiWithPathItem('/test', $pathItem);

        $this->fixer->fix($openApi);

        $this->assertOperationTypeFixed($openApi, '/test', 'Post', 'field');
        $this->assertOperationTypeFixed($openApi, '/test', 'Put', 'field');
        $this->assertOperationTypeFixed($openApi, '/test', 'Patch', 'field');
    }

    public function testFixWithPropertiesWithoutIriReference(): void
    {
        $properties = ['name' => ['type' => 'string'], 'age' => ['type' => 'integer']];
        $content = $this->createContentWithProperties($properties);
        $requestBody = new RequestBody('Test request body', $content);
        $operation = (new Operation('test', [], [], 'Test'))->withRequestBody($requestBody);
        $openApi = $this->createOpenApiWithOperation($operation);

        $this->fixer->fix($openApi);

        $resultProps = $this->getResultProperties($openApi);
        $this->assertEquals('string', $resultProps['name']['type']);
        $this->assertEquals('integer', $resultProps['age']['type']);
        $this->assertArrayNotHasKey('format', $resultProps['name']);
        $this->assertArrayNotHasKey('format', $resultProps['age']);
    }

    public function testFixWithMissingPropertiesInSchema(): void
    {
        $content = new ArrayObject(['application/json' => ['schema' => ['type' => 'object']]]);
        $requestBody = new RequestBody('Test request body', $content);
        $operation = (new Operation('test', [], [], 'Test'))->withRequestBody($requestBody);
        $openApi = $this->createOpenApiWithOperation($operation);

        $this->fixer->fix($openApi);

        $fixedContent = $openApi->getPaths()->getPath('/test')->getPost()->getRequestBody()->getContent();
        $this->assertArrayHasKey('schema', $fixedContent['application/json']);
        $this->assertArrayNotHasKey('properties', $fixedContent['application/json']['schema']);
    }

    public function testFixWithPropertyWithoutType(): void
    {
        $properties = ['field' => ['description' => 'A field without type']];
        $content = $this->createContentWithProperties($properties);
        $requestBody = new RequestBody('Test request body', $content);
        $operation = (new Operation('test', [], [], 'Test'))->withRequestBody($requestBody);
        $openApi = $this->createOpenApiWithOperation($operation);

        $this->fixer->fix($openApi);

        $resultProps = $this->getResultProperties($openApi);
        $this->assertArrayNotHasKey('type', $resultProps['field']);
        $this->assertEquals(
            'A field without type',
            $resultProps['field']['description']
        );
    }

    public function testFixWithMultiplePaths(): void
    {
        $properties = ['relation' => ['type' => 'iri-reference']];
        $content = $this->createContentWithProperties($properties);
        $requestBody = new RequestBody('Test request body', $content);
        $operation = (new Operation('test', [], [], 'Test'))->withRequestBody($requestBody);

        $pathItem1 = (new PathItem())->withPost($operation);
        $pathItem2 = (new PathItem())->withPut($operation);
        $pathsMap = ['/path1' => $pathItem1, '/path2' => $pathItem2];
        $openApi = $this->createOpenApiWithMultiplePaths($pathsMap);

        $this->fixer->fix($openApi);

        $this->assertPathIriReferenceFixed($openApi, '/path1', 'Post');
        $this->assertPathIriReferenceFixed($openApi, '/path2', 'Put');
    }

    public function testOperationNotModifiedWhenNoIriReference(): void
    {
        $content = $this->createContentWithProperties(['name' => ['type' => 'string']]);
        $requestBody = new RequestBody('Test request body', $content);
        $originalOperation = (new Operation('test', [], [], 'Test'))->withRequestBody($requestBody);
        $openApi = $this->createOpenApiWithOperation($originalOperation);

        $this->fixer->fix($openApi);
        $resultOperation = $openApi->getPaths()->getPath('/test')->getPost();

        $this->assertEquals(
            $originalOperation->getRequestBody()->getContent()->getArrayCopy(),
            $resultOperation->getRequestBody()->getContent()->getArrayCopy()
        );
    }

    public function testOperationModifiedWhenIriReferenceExists(): void
    {
        $properties = [
            'relation' => ['type' => 'iri-reference'],
            'normalField' => ['type' => 'string'],
        ];
        $content = $this->createContentWithProperties($properties);
        $requestBody = new RequestBody('Test request body', $content);
        $operation = (new Operation('test', [], [], 'Test'))->withRequestBody($requestBody);
        $openApi = $this->createOpenApiWithOperation($operation);

        $this->fixer->fix($openApi);

        $resultProperties = $this->getResultProperties($openApi);
        $this->assertEquals('string', $resultProperties['relation']['type']);
        $this->assertEquals(
            'iri-reference',
            $resultProperties['relation']['format']
        );
        $this->assertEquals('string', $resultProperties['normalField']['type']);
        $this->assertArrayNotHasKey(
            'format',
            $resultProperties['normalField']
        );
    }

    public function testFixPreservesOtherPropertiesWhenConvertingIriReference(): void
    {
        $properties = [
            'relation' => [
                'type' => 'iri-reference',
                'description' => 'A relation field',
                'example' => '/api/customers/123',
                'nullable' => true,
            ],
        ];
        $content = $this->createContentWithProperties($properties);
        $requestBody = new RequestBody('Test request body', $content);
        $operation = (new Operation('test', [], [], 'Test'))->withRequestBody($requestBody);
        $openApi = $this->createOpenApiWithOperation($operation);

        $this->fixer->fix($openApi);

        $property = $this->getResultProperties($openApi)['relation'];
        $this->assertEquals('string', $property['type']);
        $this->assertEquals('iri-reference', $property['format']);
        $this->assertEquals('A relation field', $property['description']);
        $this->assertEquals('/api/customers/123', $property['example']);
        $this->assertTrue($property['nullable']);
    }

    /**
     * @param array<string, array<string, string|int|bool|array|null>> $schemaProperties
     */
    private function createContentWithProperties(array $schemaProperties): ArrayObject
    {
        return new ArrayObject([
            'application/json' => [
                'schema' => [
                    'properties' => $schemaProperties,
                ],
            ],
        ]);
    }

    private function createOpenApiWithOperation(
        Operation $operation,
        string $path = '/test'
    ): OpenApi {
        $pathItem = (new PathItem())->withPost($operation);
        $paths = new Paths();
        $paths->addPath($path, $pathItem);

        return new OpenApi(
            new \ApiPlatform\OpenApi\Model\Info('Test', '1.0.0'),
            [],
            $paths
        );
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

    /**
     * @return array<string, array<string, string|int|bool|array|null>>
     */
    private function getResultProperties(OpenApi $openApi): array
    {
        $operation = $openApi->getPaths()->getPath('/test')->getPost();
        $content = $operation->getRequestBody()->getContent();

        return $content['application/json']['schema']['properties'];
    }

    /**
     * @param array<string, PathItem> $pathsMap
     */
    private function createOpenApiWithMultiplePaths(array $pathsMap): OpenApi
    {
        $paths = new Paths();
        foreach ($pathsMap as $path => $pathItem) {
            $paths->addPath($path, $pathItem);
        }

        return new OpenApi(
            new \ApiPlatform\OpenApi\Model\Info('Test', '1.0.0'),
            [],
            $paths
        );
    }

    private function assertPathIriReferenceFixed(
        OpenApi $openApi,
        string $path,
        string $method
    ): void {
        $pathItem = $openApi->getPaths()->getPath($path);
        $operation = match ($method) {
            'Get' => $pathItem->getGet(),
            'Post' => $pathItem->getPost(),
            'Put' => $pathItem->getPut(),
            'Patch' => $pathItem->getPatch(),
            'Delete' => $pathItem->getDelete(),
        };
        $content = $operation->getRequestBody()->getContent();
        $type = $content['application/json']['schema']['properties']['relation']['type'];
        $this->assertEquals('string', $type);
    }

    private function assertOperationTypeFixed(
        OpenApi $openApi,
        string $path,
        string $method,
        string $fieldName
    ): void {
        $pathItem = $openApi->getPaths()->getPath($path);
        $operation = match ($method) {
            'Get' => $pathItem->getGet(),
            'Post' => $pathItem->getPost(),
            'Put' => $pathItem->getPut(),
            'Patch' => $pathItem->getPatch(),
            'Delete' => $pathItem->getDelete(),
        };
        $content = $operation->getRequestBody()->getContent();
        $type = $content['application/json']['schema']['properties'][$fieldName]['type'];
        $this->assertEquals('string', $type);
    }
}
