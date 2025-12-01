<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\PathItem;

interface IriReferenceOperationContextResolverInterface
{
    public function resolve(PathItem $pathItem, string $operation): ?IriReferenceOperationContext;
}
