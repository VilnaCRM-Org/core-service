<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Serializer;

use App\Core\Customer\Application\DTO\CustomerPatch;
use App\Core\Customer\Application\DTO\StatusPatch;
use App\Core\Customer\Application\DTO\TypePatch;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class CustomerPatchPayloadDenormalizer implements
    DenormalizerInterface,
    DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;

    private const ALREADY_CALLED = 'customer_patch_payload_denormalizer_called';

    private const FIELD_MAP = [
        CustomerPatch::class => [
            'confirmed' => true,
            'email' => true,
            'id' => true,
            'initials' => true,
            'leadSource' => true,
            'phone' => true,
            'status' => true,
            'type' => true,
        ],
        StatusPatch::class => [
            'id' => true,
            'value' => true,
        ],
        TypePatch::class => [
            'id' => true,
            'value' => true,
        ],
    ];

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint
     *
     * @param array<string, mixed> $context
     */
    public function denormalize(
        mixed $data,
        string $type,
        ?string $format = null,
        array $context = []
    ): mixed {
        $context[self::ALREADY_CALLED] = true;

        if (is_array($data) && isset(self::FIELD_MAP[$type])) {
            $data = array_intersect_key($data, self::FIELD_MAP[$type]);
        }

        return $this->denormalizer->denormalize(
            $data,
            $type,
            $format,
            $context
        );
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint
     *
     * @param array<string, mixed> $context
     */
    public function supportsDenormalization(
        mixed $data,
        string $type,
        ?string $format = null,
        array $context = []
    ): bool {
        if (($context[self::ALREADY_CALLED] ?? false) === true) {
            return false;
        }

        if (($context['allow_extra_attributes'] ?? false) !== true) {
            return false;
        }

        return is_array($data) && isset(self::FIELD_MAP[$type]);
    }

    /**
     * @return array<class-string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            CustomerPatch::class => false,
            StatusPatch::class => false,
            TypePatch::class => false,
        ];
    }
}
