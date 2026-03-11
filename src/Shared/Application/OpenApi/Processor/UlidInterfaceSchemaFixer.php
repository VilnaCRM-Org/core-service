<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;

/**
 * Adds missing ulid property to UlidInterface.jsonld-output schema
 * and fixes ulid $ref to type: string in Customer schemas.
 */
final class UlidInterfaceSchemaFixer
{
    private const CUSTOMER_SCHEMAS = ['Customer.jsonld-output', 'CustomerType.jsonld-output'];

    public function process(OpenApi $openApi): OpenApi
    {
        $components = $openApi->getComponents();
        $schemas = $components->getSchemas();

        if ($schemas === null) {
            return $openApi;
        }

        $normalizer = new UlidInterfaceSchemaNormalizer();
        $schemasArray = $normalizer->normalize($schemas->getArrayCopy());
        $schemasArray = $this->replaceCustomerSchemaUlids($schemasArray, new CustomerUlidRefReplacer());

        return $openApi->withComponents($components->withSchemas(new ArrayObject($schemasArray)));
    }

    /**
     * @param array<string, array|bool|float|int|string|ArrayObject|null> $schemas
     *
     * @return array<string, array|bool|float|int|string|ArrayObject|null>
     */
    private function replaceCustomerSchemaUlids(
        array $schemas,
        CustomerUlidRefReplacer $replacer,
    ): array
    {
        foreach (self::CUSTOMER_SCHEMAS as $schemaName) {
            $schemas = $replacer->replace($schemas, $schemaName);
        }

        return $schemas;
    }
}
