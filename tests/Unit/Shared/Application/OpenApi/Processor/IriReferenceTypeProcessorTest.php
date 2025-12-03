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
use App\Shared\Application\OpenApi\Processor\IriReferenceContentTransformer;
use App\Shared\Application\OpenApi\Processor\IriReferenceContentTransformerInterface;
use App\Shared\Application\OpenApi\Processor\IriReferenceOperationContext;
use App\Shared\Application\OpenApi\Processor\IriReferenceOperationContextResolver;
use App\Shared\Application\OpenApi\Processor\IriReferenceOperationContextResolverInterface;
use App\Shared\Application\OpenApi\Processor\IriReferenceTypeProcessor;
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

        $processed = (new IriReferenceTypeProcessor())->process($openApi);
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

        $processed = (new IriReferenceTypeProcessor())->process($openApi);
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
        $processor = new IriReferenceTypeProcessor($contentTransformer);

        $paths = new Paths();
        $paths->addPath('/customers', (new PathItem())->withPost($this->createOperationWithIriReference()));

        $openApi = new OpenApi(
            new Info('title', '1.0', ''),
            [new Server('https://localhost')],
            $paths,
            new Components()
        );

        $processor->process($openApi);

        self::assertTrue($contentTransformer->invoked);
    }

    public function testProcessUsesInjectedContextResolver(): void
    {
        $resolver = new RecordingContextResolver();
        $processor = new IriReferenceTypeProcessor(null, $resolver);

        $paths = new Paths();
        $paths->addPath('/customers', (new PathItem())->withPost($this->createOperationWithIriReference()));

        $openApi = new OpenApi(
            new Info('title', '1.0', ''),
            [new Server('https://localhost')],
            $paths,
            new Components()
        );

        $processor->process($openApi);

        self::assertTrue($resolver->invoked);
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

final class RecordingContentTransformer implements IriReferenceContentTransformerInterface
{
    public bool $invoked = false;

    public function __construct(
        private readonly IriReferenceContentTransformer $inner = new IriReferenceContentTransformer()
    ) {
    }

    public function transform(ArrayObject $content): ?array
    {
        $this->invoked = true;

        return $this->inner->transform($content) ?? $content->getArrayCopy();
    }
}

final class RecordingContextResolver implements IriReferenceOperationContextResolverInterface
{
    public bool $invoked = false;

    public function __construct(
        private readonly IriReferenceOperationContextResolver $inner = new IriReferenceOperationContextResolver()
    ) {
    }

    public function resolve(PathItem $pathItem, string $operation): ?IriReferenceOperationContext
    {
        $this->invoked = true;

        return $this->inner->resolve($pathItem, $operation);
    }
}
