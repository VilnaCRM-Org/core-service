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
        // Create a mock of ContextBuilder
        $contextBuilderMock = $this->createMock(ContextBuilder::class);

        // Define the expected parameters and return value
        $params = [/* array of Parameter objects */];
        $expectedContent = new \ArrayObject([
            'application/ld+json' => [
                'schema' => [
                    'type' => 'object',
                    'properties' => [/* properties based on $params */],
                    'required' => [/* required fields based on $params */],
                ],
                'example' => [/* example data based on $params */],
            ],
        ]);

        // Configure the mock to return the expected content
        $contextBuilderMock->expects($this->once())
            ->method('build')
            ->with($params)
            ->willReturn($expectedContent);

        // Instantiate RequestPatchBuilder with the mocked ContextBuilder
        $requestPatchBuilder = new RequestPatchBuilder($contextBuilderMock);

        // Call the method under test
        $actualRequestBody = $requestPatchBuilder->build($params);

        // Assert that the actual object is an instance of RequestBody
        $this->assertInstanceOf(RequestBody::class, $actualRequestBody);

        // Optionally, assert that the content matches the expected content
        $this->assertEquals($expectedContent, $actualRequestBody->getContent());
    }
}
