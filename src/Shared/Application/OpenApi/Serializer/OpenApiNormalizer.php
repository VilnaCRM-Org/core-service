<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Serializer;

use ApiPlatform\OpenApi\OpenApi;
use stdClass;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizer that cleans up the OpenAPI spec by removing null values and empty arrays
 * to ensure compliance with OpenAPI 3.1 specification.
 */
final class OpenApiNormalizer implements NormalizerInterface
{
    public function __construct(
        private readonly NormalizerInterface $decorated,
        private readonly DataCleaner $dataCleaner
    ) {
    }

    /**
     * @param array<string, bool|int|string> $context
     */
    public function normalize(
        mixed $object,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|\ArrayObject|null {
        $data = $this->decorated->normalize($object, $format, $context);

        if (!is_array($data)) {
            return $data;
        }

        $cleaned = $this->dataCleaner->clean($data);

        return $this->normalizeWebhooks($cleaned);
    }

    /**
     * @param array<string, bool|int|string> $context
     */
    public function supportsNormalization(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): bool {
        return $data instanceof OpenApi;
    }

    /**
     * @return array<string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            OpenApi::class => true,
        ];
    }

    /**
     * Ensures webhooks field is serialized as empty object {} instead of empty array [].
     *
     * @param array<array-key, array|string|int|float|bool|\ArrayObject|null> $data
     *
     * @return array<array-key, array|string|int|float|bool|\ArrayObject|stdClass|null>
     */
    private function normalizeWebhooks(array $data): array
    {
        if (isset($data['webhooks']) && $data['webhooks'] === []) {
            $data['webhooks'] = new stdClass();
        }

        return $data;
    }
}
