<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;

final class CustomerTypeRequestBodyPathUpdater
{
    public function __construct(
        private readonly RequestBodySchemaRefUpdater $requestBodyUpdater
    ) {
    }

    public function update(PathItem $pathItem): PathItem
    {
        $post = $pathItem->getPost();

        if (! $post instanceof Operation) {
            return $pathItem;
        }

        $updatedPost = $this->requestBodyUpdater->update($post);

        return $updatedPost === $post
            ? $pathItem
            : $pathItem->withPost($updatedPost);
    }
}
