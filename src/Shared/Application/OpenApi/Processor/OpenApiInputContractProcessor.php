<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Mapper\PathsMapper;

final class OpenApiInputContractProcessor implements OpenApiProcessorInterface
{
    private const INPUT_REQUEST_BODY_SCHEMA_REFS = [
        '/api/customers' => [
            'Post' => '#/components/schemas/Customer.CustomerCreate',
        ],
        '/api/customers/{ulid}' => [
            'Put' => '#/components/schemas/Customer.CustomerPut',
            'Patch' => '#/components/schemas/Customer.CustomerPatch.jsonMergePatch',
        ],
        '/api/customer_statuses' => [
            'Post' => '#/components/schemas/CustomerStatus.StatusCreate',
        ],
        '/api/customer_statuses/{ulid}' => [
            'Put' => '#/components/schemas/CustomerStatus.StatusPut',
            'Patch' => '#/components/schemas/CustomerStatus.StatusPatch.jsonMergePatch',
        ],
        '/api/customer_types' => [
            'Post' => '#/components/schemas/CustomerType.TypeCreate',
        ],
        '/api/customer_types/{ulid}' => [
            'Put' => '#/components/schemas/CustomerType.TypePut',
            'Patch' => '#/components/schemas/CustomerType.TypePatch.jsonMergePatch',
        ],
    ];

    public function __construct(
        private readonly OpenApiInputSchemaUpdater $schemaUpdater,
        private readonly RequestBodyPathUpdater $pathUpdater
    ) {
    }

    public function process(OpenApi $openApi): OpenApi
    {
        $openApi = $this->schemaUpdater->update($openApi);

        return PathsMapper::map(
            $openApi,
            fn (PathItem $pathItem, string $path): PathItem => \is_array(
                self::INPUT_REQUEST_BODY_SCHEMA_REFS[$path] ?? null
            )
                ? $this->pathUpdater->update(
                    $pathItem,
                    self::INPUT_REQUEST_BODY_SCHEMA_REFS[$path]
                )
                : $pathItem
        );
    }
}
