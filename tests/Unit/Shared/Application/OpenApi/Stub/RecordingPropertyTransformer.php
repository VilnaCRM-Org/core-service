<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Stub;

use App\Shared\Application\OpenApi\Transformer\IriReferencePropertyTransformerInterface;

final class RecordingPropertyTransformer implements IriReferencePropertyTransformerInterface
{
    public const TRANSFORMED_FLAG = ['type' => 'string', 'format' => 'custom'];

    private bool $invoked = false;

    /**
     * @param array<string, mixed> $schema
     *
     * @return array<string, mixed>
     */
    public function transform(array $schema): array
    {
        $this->invoked = true;

        return self::TRANSFORMED_FLAG;
    }

    public function wasInvoked(): bool
    {
        return $this->invoked;
    }
}
