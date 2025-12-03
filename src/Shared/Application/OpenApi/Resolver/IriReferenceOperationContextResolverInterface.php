<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Resolver;

use ApiPlatform\OpenApi\Model\PathItem;
use App\Shared\Application\OpenApi\ValueObject\IriReferenceOperationContext;

interface IriReferenceOperationContextResolverInterface
{
    public function resolve(PathItem $pathItem, string $operation): ?IriReferenceOperationContext;
}
