<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Server;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Processor\IriReferenceTypeProcessor;
use App\Shared\Application\OpenApi\Resolver\IriReferenceOperationContextResolver;
use App\Shared\Application\OpenApi\Transformer\IriReferenceContentTransformer;
use App\Shared\Application\OpenApi\Transformer\IriReferenceMediaTypeTransformer;
use App\Shared\Application\OpenApi\Transformer\IriReferencePropertyTransformer;
use App\Tests\Unit\Shared\Application\OpenApi\Stub\RecordingContentTransformer;
use App\Tests\Unit\Shared\Application\OpenApi\Stub\RecordingContextResolver;
use App\Tests\Unit\UnitTestCase;
use ArrayObject;

final class IriReferenceTypeProcessorTest extends UnitTestCase
{
    public function testProcessNormalizesOperationsContainingIriReferences(): void
    {
        $paths = new Paths();
        $pathItem = (new PathItem())
            ->withPost($this->createOperationWithIriReference())
            ->withGet($this->createOperationWithoutIriReference());
        $paths->addPath('/customers', $pathItem);

        $openApi = new OpenApi(
            new Info('VilnaCRM', '1.0', 'Spec under test'),
            [new Server('https://localhost')],
            $paths,
            new Components()
        );

        $processor = new IriReferenceTypeProcessor(
            new IriReferenceContentTransformer(
                new IriReferenceMediaTypeTransformer(
                    new IriReferencePropertyTransformer()
                )
            ),
            new IriReferenceOperationContextResolver()
        );
        $processed = $processor->process($openApi);
        $updatedPath = $processed->getPaths()->getPath('/customers');

        $postContent = $updatedPath->getPost()?->getRequestBody()?->getContent();
        self::assertNotNull($postContent);
        $postSchema = $postContent['application/json']['schema']['properties']['relation'];
        self::assertSame('string', $postSchema['type']);
        self::assertSame('iri-reference', $postSchema['format']);

        $getContent = $updatedPath->getGet()?->getRequestBody()?->getContent();
        self::assertSame(
            $this->createNonIriSchema()['application/json'],
            $getContent['application/json']
        );
    }

    public function testProcessSkipsOperationsWithoutRenderableContent(): void
    {
        $paths = new Paths();
        $paths->addPath(
            '/customers',
            (new PathItem())
                ->withPatch(new Operation())
                ->withDelete(
                    (new Operation())->withRequestBody(new RequestBody('without content'))
                )
        );

        $openApi = new OpenApi(
            new Info('title', '1.0', ''),
            [new Server('https://localhost')],
            $paths,
            new Components()
        );

        $processed = (new IriReferenceTypeProcessor(
            new RecordingContentTransformer(),
            new RecordingContextResolver()
        ))->process($openApi);
        $processedPath = $processed->getPaths()->getPath('/customers');

        self::assertSame(
            null,
            $processedPath->getPatch()?->getRequestBody()
        );
        self::assertSame(
            null,
            $processedPath->getDelete()?->getRequestBody()?->getContent()
        );
    }

    public function testProcessUsesInjectedContentTransformer(): void
    {
        $contentTransformer = new RecordingContentTransformer();
        $processor = new IriReferenceTypeProcessor(
            $contentTransformer,
            new RecordingContextResolver()
        );

        $paths = new Paths();
        $paths->addPath(
            '/customers',
            (new PathItem())->withPost($this->createOperationWithIriReference())
        );

        $openApi = new OpenApi(
            new Info('title', '1.0', ''),
            [new Server('https://localhost')],
            $paths,
            new Components()
        );

        $processor->process($openApi);

        self::assertTrue($contentTransformer->wasInvoked());
    }

    public function testProcessUsesInjectedContextResolver(): void
    {
        $resolver = new RecordingContextResolver();
        $processor = new IriReferenceTypeProcessor(
            new RecordingContentTransformer(),
            $resolver
        );

        $paths = new Paths();
        $paths->addPath(
            '/customers',
            (new PathItem())->withPost($this->createOperationWithIriReference())
        );

        $openApi = new OpenApi(
            new Info('title', '1.0', ''),
            [new Server('https://localhost')],
            $paths,
            new Components()
        );

        $processor->process($openApi);

        self::assertTrue($resolver->wasInvoked());
    }

    public function testProcessPreservesExtensionProperties(): void
    {
        $paths = new Paths();
        $paths->addPath(
            '/customers',
            (new PathItem())->withPost($this->createOperationWithIriReference())
        );

        $openApi = (new OpenApi(
            new Info('title', '1.0', ''),
            [new Server('https://localhost')],
            $paths,
            new Components()
        ))->withExtensionProperty('x-custom', 'value');

        $processor = new IriReferenceTypeProcessor(
            new RecordingContentTransformer(),
            new RecordingContextResolver()
        );
        $result = $processor->process($openApi);

        self::assertSame(['x-custom' => 'value'], $result->getExtensionProperties());
    }

    private function createOperationWithIriReference(): Operation
    {
        return (new Operation())
            ->withRequestBody(
                (new RequestBody())
                    ->withContent(new ArrayObject($this->createIriSchema()))
            );
    }

    private function createOperationWithoutIriReference(): Operation
    {
        return (new Operation())
            ->withRequestBody(
                (new RequestBody())
                    ->withContent(new ArrayObject($this->createNonIriSchema()))
            );
    }

    /**
     * @return array<string, array<string, array<string, array<string, string>>>>
     */
    private function createIriSchema(): array
    {
        return [
            'application/json' => [
                'schema' => [
                    'properties' => [
                        'relation' => ['type' => 'iri-reference'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<string, array<string, array<string, string>>>>
     */
    private function createNonIriSchema(): array
    {
        return [
            'application/json' => [
                'schema' => [
                    'properties' => [
                        'name' => ['type' => 'string'],
                    ],
                ],
            ],
        ];
    }
}
