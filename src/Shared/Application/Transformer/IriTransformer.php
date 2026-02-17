<?php

declare(strict_types=1);

namespace App\Shared\Application\Transformer;

final readonly class IriTransformer implements IriTransformerInterface
{
    #[\Override]
    public function transform(string $idOrIri): string
    {
        if (!str_starts_with($idOrIri, '/')) {
            return $idOrIri;
        }

        $path = parse_url($idOrIri, PHP_URL_PATH);
        return $path !== null ? basename($path) : $idOrIri;
    }
}
