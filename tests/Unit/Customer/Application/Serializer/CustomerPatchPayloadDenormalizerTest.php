<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Serializer;

use App\Core\Customer\Application\DTO\TypePatch;
use App\Core\Customer\Application\Serializer\CustomerPatchPayloadDenormalizer;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class CustomerPatchPayloadDenormalizerTest extends UnitTestCase
{
    private DenormalizerInterface|MockObject $innerDenormalizer;
    private CustomerPatchPayloadDenormalizer $denormalizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->innerDenormalizer = $this->createMock(DenormalizerInterface::class);
        $this->denormalizer = new CustomerPatchPayloadDenormalizer();
        $this->denormalizer->setDenormalizer($this->innerDenormalizer);
    }

    public function testSupportsPatchDtoWhenExtraAttributesAreAllowed(): void
    {
        self::assertTrue($this->denormalizer->supportsDenormalization(
            ['value' => 'VIP'],
            TypePatch::class,
            'json',
            ['allow_extra_attributes' => true]
        ));
    }

    public function testDoesNotSupportPatchDtoWhenExtraAttributesAreStrict(): void
    {
        self::assertFalse($this->denormalizer->supportsDenormalization(
            ['value' => 'VIP'],
            TypePatch::class,
            'json',
            ['allow_extra_attributes' => false]
        ));
    }

    public function testDenormalizeRemovesUnsupportedPayloadKeys(): void
    {
        $payload = [
            'value' => 'VIP',
            ".\x0E" => [[]],
            'unknown' => 'ignored',
            'id' => '/api/customer_types/01JGVZ9YGXE8P3Q2R5T7W9Y0A2',
        ];
        $expectedDto = new TypePatch(
            'VIP',
            '/api/customer_types/01JGVZ9YGXE8P3Q2R5T7W9Y0A2'
        );

        $this->innerDenormalizer
            ->expects($this->once())
            ->method('denormalize')
            ->with(
                [
                    'value' => 'VIP',
                    'id' => '/api/customer_types/01JGVZ9YGXE8P3Q2R5T7W9Y0A2',
                ],
                TypePatch::class,
                'json',
                $this->callback(static fn (array $context): bool => (
                    $context['customer_patch_payload_denormalizer_called'] ?? false
                ) === true)
            )
            ->willReturn($expectedDto);

        self::assertSame(
            $expectedDto,
            $this->denormalizer->denormalize(
                $payload,
                TypePatch::class,
                'json',
                ['allow_extra_attributes' => true]
            )
        );
    }
}
