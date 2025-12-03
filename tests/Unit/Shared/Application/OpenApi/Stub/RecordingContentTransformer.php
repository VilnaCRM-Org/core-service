<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Stub;

use App\Shared\Application\OpenApi\Transformer\IriReferenceContentTransformer;
use App\Shared\Application\OpenApi\Transformer\IriReferenceContentTransformerInterface;
use ArrayObject;

final class RecordingContentTransformer implements IriReferenceContentTransformerInterface
{
    private bool $invoked = false;

    public function __construct(
        private readonly IriReferenceContentTransformer $inner = new IriReferenceContentTransformer()
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function transform(ArrayObject $content): ?array
    {
        $this->invoked = true;

        return $this->inner->transform($content) ?? $content->getArrayCopy();
    }

    public function wasInvoked(): bool
    {
        return $this->invoked;
    }
}
