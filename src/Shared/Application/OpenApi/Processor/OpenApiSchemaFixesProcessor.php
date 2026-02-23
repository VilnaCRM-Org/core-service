<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;

final class OpenApiSchemaFixesProcessor
{
    public function __construct(
        private HydraCollectionSchemaFixer $hydraCollectionSchemaFixer,
        private UlidSchemaFixer $ulidSchemaFixer
    ) {
    }

    public function process(OpenApi $openApi): OpenApi
    {
        $components = $openApi->getComponents();
        $schemas = $components->getSchemas() ?? new ArrayObject();

        $schemas = $this->hydraCollectionSchemaFixer->apply($schemas);
        $schemas = $this->ulidSchemaFixer->apply($schemas);

        return $openApi->withComponents($components->withSchemas($schemas));
    }
}
