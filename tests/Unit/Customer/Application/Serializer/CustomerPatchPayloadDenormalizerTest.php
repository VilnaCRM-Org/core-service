<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Serializer;

use App\Core\Customer\Application\DTO\CustomerPatch;
use App\Core\Customer\Application\DTO\StatusPatch;
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

    public function testSupportsPatchDtoWhenExtraAttributesAreStrict(): void
    {
        self::assertTrue($this->denormalizer->supportsDenormalization(
            ['value' => 'VIP'],
            TypePatch::class,
            'json',
            ['allow_extra_attributes' => false]
        ));
    }

    public function testDoesNotSupportPatchDtoWhenAlreadyCalled(): void
    {
        self::assertFalse($this->denormalizer->supportsDenormalization(
            ['value' => 'VIP'],
            TypePatch::class,
            'json',
            ['customer_patch_payload_denormalizer_called' => true]
        ));
    }

    public function testDoesNotSupportPatchDtoWhenPayloadIsNotArray(): void
    {
        self::assertFalse($this->denormalizer->supportsDenormalization(
            'VIP',
            TypePatch::class,
            'json',
            ['allow_extra_attributes' => true]
        ));
    }

    public function testReturnsSupportedTypes(): void
    {
        self::assertSame([
            CustomerPatch::class => false,
            StatusPatch::class => false,
            TypePatch::class => false,
        ], $this->denormalizer->getSupportedTypes(null));
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
                $this->callback(static function (array $data): bool {
                    self::assertSame([
                        'value' => 'VIP',
                        'id' => '/api/customer_types/01JGVZ9YGXE8P3Q2R5T7W9Y0A2',
                    ], $data);

                    return true;
                }),
                TypePatch::class,
                'json',
                $this->callback(static fn (array $context): bool => ($context['customer_patch_payload_denormalizer_called'] ?? false) === true)
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

    public function testDenormalizePreservesPayloadKeysByDefault(): void
    {
        $payload = [
            'value' => 'VIP',
            'unknown' => 'rejected downstream',
        ];
        $expectedDto = new TypePatch('VIP');

        $this->innerDenormalizer
            ->expects($this->once())
            ->method('denormalize')
            ->with(
                $payload,
                TypePatch::class,
                'json',
                $this->callback(static fn (array $context): bool => ($context['customer_patch_payload_denormalizer_called'] ?? false) === true)
            )
            ->willReturn($expectedDto);

        self::assertSame(
            $expectedDto,
            $this->denormalizer->denormalize(
                $payload,
                TypePatch::class,
                'json'
            )
        );
    }

    public function testDenormalizePreservesPayloadKeysWhenExtraAttributesAreTruthyButNotTrue(): void
    {
        $payload = [
            'value' => 'VIP',
            'unknown' => 'rejected downstream',
        ];
        $expectedDto = new TypePatch('VIP');

        $this->innerDenormalizer
            ->expects($this->once())
            ->method('denormalize')
            ->with(
                $payload,
                TypePatch::class,
                'json',
                $this->callback(static fn (array $context): bool => ($context['customer_patch_payload_denormalizer_called'] ?? false) === true)
            )
            ->willReturn($expectedDto);

        self::assertSame(
            $expectedDto,
            $this->denormalizer->denormalize(
                $payload,
                TypePatch::class,
                'json',
                ['allow_extra_attributes' => 1]
            )
        );
    }

    public function testDenormalizePreservesNonArrayPayloadWhenExtraAttributesAreAllowed(): void
    {
        $expectedDto = new TypePatch('VIP');

        $this->innerDenormalizer
            ->expects($this->once())
            ->method('denormalize')
            ->with(
                'VIP',
                TypePatch::class,
                'json',
                $this->callback(static fn (array $context): bool => ($context['customer_patch_payload_denormalizer_called'] ?? false) === true)
            )
            ->willReturn($expectedDto);

        self::assertSame(
            $expectedDto,
            $this->denormalizer->denormalize(
                'VIP',
                TypePatch::class,
                'json',
                ['allow_extra_attributes' => true]
            )
        );
    }

    public function testDenormalizePreservesStrictPayloadKeys(): void
    {
        $payload = [
            'value' => 'VIP',
            'unknown' => 'rejected downstream',
        ];
        $expectedDto = new TypePatch('VIP');

        $this->innerDenormalizer
            ->expects($this->once())
            ->method('denormalize')
            ->with(
                $payload,
                TypePatch::class,
                'json',
                $this->callback(static fn (array $context): bool => ($context['customer_patch_payload_denormalizer_called'] ?? false) === true)
            )
            ->willReturn($expectedDto);

        self::assertSame(
            $expectedDto,
            $this->denormalizer->denormalize(
                $payload,
                TypePatch::class,
                'json',
                ['allow_extra_attributes' => false]
            )
        );
    }
}
