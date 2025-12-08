<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Stub;

use App\Shared\Application\OpenApi\Transformer\IriReferenceMediaTypeTransformerInterface;

final class RecordingMediaTypeTransformer implements IriReferenceMediaTypeTransformerInterface
{
    public const TRANSFORMED_FLAG = ['transformed' => true];

    private bool $invoked = false;

    /**
     * @param array<string, mixed> $mediaType
     *
     * @return array<string, mixed>
     */
    public function transform(array $mediaType): array
    {
        $this->invoked = true;

        return self::TRANSFORMED_FLAG;
    }

    public function wasInvoked(): bool
    {
        return $this->invoked;
    }
}
