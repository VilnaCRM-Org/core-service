<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;

final class OpenApiSchemaFixesProcessor
{
    public function __construct(
        private HydraCollectionSchemaFixer $hydraCollectionSchemaFixer
    ) {
    }

    public function process(OpenApi $openApi): OpenApi
    {
        $components = $openApi->getComponents() ?? new Components(new ArrayObject());
        $schemas = $components->getSchemas() ?? new ArrayObject();

        $schemas = $this->hydraCollectionSchemaFixer->apply($schemas);

        return $openApi->withComponents($components->withSchemas($schemas));
    }
}
