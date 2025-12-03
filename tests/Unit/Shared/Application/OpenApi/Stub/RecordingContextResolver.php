<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Stub;

use ApiPlatform\OpenApi\Model\PathItem;
use App\Shared\Application\OpenApi\Resolver\IriReferenceOperationContextResolver;
use App\Shared\Application\OpenApi\Resolver\IriReferenceOperationContextResolverInterface;
use App\Shared\Application\OpenApi\ValueObject\IriReferenceOperationContext;

final class RecordingContextResolver implements IriReferenceOperationContextResolverInterface
{
    private bool $invoked = false;

    public function __construct(
        private readonly IriReferenceOperationContextResolver $inner = new IriReferenceOperationContextResolver()
    ) {
    }

    public function resolve(PathItem $pathItem, string $operation): ?IriReferenceOperationContext
    {
        $this->invoked = true;

        return $this->inner->resolve($pathItem, $operation);
    }

    public function wasInvoked(): bool
    {
        return $this->invoked;
    }
}
