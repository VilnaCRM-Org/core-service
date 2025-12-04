<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\RequestBody;
use App\Shared\Application\OpenApi\Resolver\IriReferenceOperationContextResolver;
use App\Tests\Unit\UnitTestCase;
use ArrayObject;

final class IriReferenceOperationContextResolverTest extends UnitTestCase
{
    private IriReferenceOperationContextResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new IriReferenceOperationContextResolver();
    }

    public function testResolveReturnsContextWhenOperationHasRequestBody(): void
    {
        $requestBody = (new RequestBody())->withContent(
            new ArrayObject(['application/json' => []])
        );
        $pathItem = (new PathItem())->withPost(
            (new Operation('op'))->withRequestBody($requestBody)
        );

        $context = $this->resolver->resolve($pathItem, 'Post');

        self::assertNotNull($context);
        self::assertSame($requestBody, $context->requestBody);
    }

    public function testResolveReturnsNullWhenOperationMissing(): void
    {
        $pathItem = new PathItem();

        self::assertNull($this->resolver->resolve($pathItem, 'Get'));
    }
}
