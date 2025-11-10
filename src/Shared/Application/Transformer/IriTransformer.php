<?php

declare(strict_types=1);

namespace App\Shared\Application\Transformer;

final readonly class IriTransformer implements IriTransformerInterface
{
    public function transform(string $idOrIri): string
    {
        if (!str_starts_with($idOrIri, '/')) {
            return $idOrIri;
        }

        return $this->extractBasename($idOrIri);
    }

    private function extractBasename(string $iri): string
    {
        $path = parse_url($iri, PHP_URL_PATH);

        if (!$path) {
            return $iri;
        }

        return basename($path);
    }
}
