<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Mapper\PathItemOperationMapper;
use App\Shared\Application\OpenApi\Mapper\PathsMapper;
use ArrayObject;

final class OpenApiSchemaFixesProcessor implements OpenApiProcessorInterface
{
    public function __construct(
        private HydraCollectionSchemaFixer $hydraCollectionSchemaFixer,
        private OpenApiOperationSchemaFixer $operationSchemaFixer
    ) {
    }

    public function process(OpenApi $openApi): OpenApi
    {
        $components = $openApi->getComponents() ?? new Components(new ArrayObject());
        $schemas = $components->getSchemas() ?? new ArrayObject();

        $schemas = $this->hydraCollectionSchemaFixer->apply($schemas);
        $openApi = $openApi->withComponents($components->withSchemas($schemas));

        return PathsMapper::map(
            $openApi,
            fn (PathItem $pathItem): PathItem => PathItemOperationMapper::map(
                $pathItem,
                fn (Operation $operation): Operation => $this->operationSchemaFixer->fix($operation)
            )
        );
    }
}
