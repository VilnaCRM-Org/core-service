<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Stub;

use App\Shared\Application\OpenApi\Transformer\IriReferenceContentTransformer;
use App\Shared\Application\OpenApi\Transformer\IriReferenceContentTransformerInterface;
use App\Shared\Application\OpenApi\Transformer\IriReferenceMediaTypeTransformer;
use App\Shared\Application\OpenApi\Transformer\IriReferencePropertyTransformer;
use ArrayObject;

final class RecordingContentTransformer implements IriReferenceContentTransformerInterface
{
    private bool $invoked = false;
    private readonly IriReferenceContentTransformer $inner;

    public function __construct()
    {
        $this->inner = new IriReferenceContentTransformer(
            new IriReferenceMediaTypeTransformer(
                new IriReferencePropertyTransformer()
            )
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    #[\Override]
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
