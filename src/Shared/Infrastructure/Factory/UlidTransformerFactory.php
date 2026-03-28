<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Factory;

use App\Shared\Infrastructure\Transformer\SymfonyUlidBinaryTransformer;
use App\Shared\Infrastructure\Transformer\UlidRepresentationTransformer;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use App\Shared\Infrastructure\Transformer\UlidValueTransformer;
use App\Shared\Infrastructure\Validator\UlidValidator;

final class UlidTransformerFactory
{
    public static function create(): UlidTransformer
    {
        $ulidFactory = new UlidFactory();

        return new UlidTransformer(
            $ulidFactory,
            new UlidValidator(),
            new UlidValueTransformer(
                $ulidFactory,
                new UlidRepresentationTransformer(),
                new SymfonyUlidBinaryTransformer()
            )
        );
    }
}
