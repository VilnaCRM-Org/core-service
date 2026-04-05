<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ArrayObject;

final class HydraCollectionSchemaFixer
{
    public function __construct(
        private HydraViewExampleUpdater $viewExampleUpdater,
        private HydraCollectionSchemasUpdater $schemasUpdater
    ) {
    }

    /**
     * @param ArrayObject<string, array|bool|float|int|string|ArrayObject|null> $schemas
     */
    public function apply(ArrayObject $schemas): ArrayObject
    {
        return $this->schemasUpdater->update($schemas);
    }

    /**
     * @param array<string, array|bool|float|int|string|ArrayObject|null> $schema
     *
     * @return array<string, array|bool|float|int|string|ArrayObject|null>|null
     */
    public function fixSchema($schema)
    {
        return $this->viewExampleUpdater->update(
            SchemaNormalizer::normalize($schema)
        );
    }
}
