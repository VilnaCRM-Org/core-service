<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Mapper\PathsMapper;

final class OpenApiInputContractProcessor implements OpenApiProcessorInterface
{
    private const CUSTOMER_TYPES_PATH = '/api/customer_types';

    public function __construct(
        private readonly OpenApiInputSchemaUpdater $schemaUpdater,
        private readonly CustomerTypeRequestBodyPathUpdater $pathUpdater
    ) {
    }

    public function process(OpenApi $openApi): OpenApi
    {
        $openApi = $this->schemaUpdater->update($openApi);

        return PathsMapper::map(
            $openApi,
            fn (PathItem $pathItem, string $path): PathItem => $path === self::CUSTOMER_TYPES_PATH
                ? $this->pathUpdater->update($pathItem)
                : $pathItem
        );
    }
}
