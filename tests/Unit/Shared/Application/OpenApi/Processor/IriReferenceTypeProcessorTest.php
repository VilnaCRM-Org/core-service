<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Processor\IriReferenceTypeProcessor;
use App\Tests\Unit\UnitTestCase;
use ArrayObject;

final class IriReferenceTypeProcessorTest extends UnitTestCase
{
    private IriReferenceTypeProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->processor = new IriReferenceTypeProcessor();
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

    public function testProcessWithOperationWithoutRequestBody(): void
    {
        $operation = new Operation('testOp', [], [], 'Test');
        $pathItem = (new PathItem())->withPost($operation);
        $paths = new Paths();
        $paths->addPath('/test', $pathItem);

        $openApi = new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            $paths
        );

        $result = $this->processor->process($openApi);

        $resultPath = $result->getPaths()->getPath('/test')->getPost();
        $this->assertNull($resultPath->getRequestBody());
    }

    public function testProcessWithIriReferenceType(): void
    {
        $content = new ArrayObject([
            'application/json' => [
                'schema' => [
                    'properties' => [
                        'customer' => [
                            'type' => 'iri-reference',
                        ],
                    ],
                ],
            ],
        ]);

        $requestBody = (new RequestBody('Test body', $content));
        $operation = (new Operation('testOp', [], [], 'Test'))->withRequestBody($requestBody);
        $pathItem = (new PathItem())->withPost($operation);
        $paths = new Paths();
        $paths->addPath('/test', $pathItem);

        $openApi = new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            $paths
        );

        $result = $this->processor->process($openApi);

        $resultContent = $result->getPaths()->getPath('/test')->getPost()->getRequestBody()->getContent();
        $schema = $resultContent['application/json']['schema'];
        $this->assertEquals('string', $schema['properties']['customer']['type']);
        $this->assertEquals('iri-reference', $schema['properties']['customer']['format']);
    }

    public function testProcessWithNonIriReferenceType(): void
    {
        $content = new ArrayObject([
            'application/json' => [
                'schema' => [
                    'properties' => [
                        'name' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
        ]);

        $requestBody = (new RequestBody('Test body', $content));
        $operation = (new Operation('testOp', [], [], 'Test'))->withRequestBody($requestBody);
        $pathItem = (new PathItem())->withPost($operation);
        $paths = new Paths();
        $paths->addPath('/test', $pathItem);

        $openApi = new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            $paths
        );

        $result = $this->processor->process($openApi);

        $resultContent = $result->getPaths()->getPath('/test')->getPost()->getRequestBody()->getContent();
        $schema = $resultContent['application/json']['schema'];
        $this->assertEquals('string', $schema['properties']['name']['type']);
        $this->assertArrayNotHasKey('format', $schema['properties']['name']);
    }

    public function testProcessWithMultipleIriReferences(): void
    {
        $content = new ArrayObject([
            'application/json' => [
                'schema' => [
                    'properties' => [
                        'customer' => [
                            'type' => 'iri-reference',
                        ],
                        'status' => [
                            'type' => 'iri-reference',
                        ],
                        'name' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
        ]);

        $requestBody = (new RequestBody('Test body', $content));
        $operation = (new Operation('testOp', [], [], 'Test'))->withRequestBody($requestBody);
        $pathItem = (new PathItem())->withPost($operation);
        $paths = new Paths();
        $paths->addPath('/test', $pathItem);

        $openApi = new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            $paths
        );

        $result = $this->processor->process($openApi);

        $resultContent = $result->getPaths()->getPath('/test')->getPost()->getRequestBody()->getContent();
        $schema = $resultContent['application/json']['schema'];

        $this->assertEquals('string', $schema['properties']['customer']['type']);
        $this->assertEquals('iri-reference', $schema['properties']['customer']['format']);
        $this->assertEquals('string', $schema['properties']['status']['type']);
        $this->assertEquals('iri-reference', $schema['properties']['status']['format']);
        $this->assertEquals('string', $schema['properties']['name']['type']);
        $this->assertArrayNotHasKey('format', $schema['properties']['name']);
    }

    public function testProcessWithAllOperationTypes(): void
    {
        $content = new ArrayObject([
            'application/json' => [
                'schema' => [
                    'properties' => [
                        'ref' => [
                            'type' => 'iri-reference',
                        ],
                    ],
                ],
            ],
        ]);

        $requestBody = (new RequestBody('Test body', $content));
        $postOp = (new Operation('post', [], [], 'Post'))->withRequestBody($requestBody);
        $putOp = (new Operation('put', [], [], 'Put'))->withRequestBody($requestBody);
        $patchOp = (new Operation('patch', [], [], 'Patch'))->withRequestBody($requestBody);

        $pathItem = (new PathItem())
            ->withPost($postOp)
            ->withPut($putOp)
            ->withPatch($patchOp);

        $paths = new Paths();
        $paths->addPath('/test', $pathItem);

        $openApi = new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            $paths
        );

        $result = $this->processor->process($openApi);

        $resultPath = $result->getPaths()->getPath('/test');

        $postContent = $resultPath->getPost()->getRequestBody()->getContent();
        $this->assertEquals('string', $postContent['application/json']['schema']['properties']['ref']['type']);

        $putContent = $resultPath->getPut()->getRequestBody()->getContent();
        $this->assertEquals('string', $putContent['application/json']['schema']['properties']['ref']['type']);

        $patchContent = $resultPath->getPatch()->getRequestBody()->getContent();
        $this->assertEquals('string', $patchContent['application/json']['schema']['properties']['ref']['type']);
    }

    public function testProcessWithEmptyContent(): void
    {
        $content = new ArrayObject([]);

        $requestBody = (new RequestBody('Test body', $content));
        $operation = (new Operation('testOp', [], [], 'Test'))->withRequestBody($requestBody);
        $pathItem = (new PathItem())->withPost($operation);
        $paths = new Paths();
        $paths->addPath('/test', $pathItem);

        $openApi = new OpenApi(
            new Info('Test', '1.0.0'),
            [],
            $paths
        );

        $result = $this->processor->process($openApi);

        // Should not throw exception and return unchanged
        $this->assertInstanceOf(OpenApi::class, $result);
        $resultContent = $result->getPaths()->getPath('/test')->getPost()->getRequestBody()->getContent();
        $this->assertCount(0, $resultContent);
    }
}
