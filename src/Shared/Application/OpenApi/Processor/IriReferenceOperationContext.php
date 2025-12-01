<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\RequestBody;
use ArrayObject;

final readonly class IriReferenceOperationContext
{
    public function __construct(
        public Operation $operation,
        public RequestBody $requestBody,
        public ArrayObject $content
    ) {
    }
}
