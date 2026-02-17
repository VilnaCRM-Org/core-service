<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Serializer;

use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Cleaner\DataCleaner;
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
    #[\Override]
    public function normalize(
        mixed $object,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|\ArrayObject|null {
        $data = $this->decorated->normalize($object, $format, $context);

        return is_array($data)
            ? $this->normalizeWebhooks($this->dataCleaner->clean($data))
            : $data;
    }

    /**
     * @param array<string, bool|int|string> $context
     */
    #[\Override]
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
    #[\Override]
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
        return isset($data['webhooks']) && $data['webhooks'] === []
            ? $this->withEmptyWebhooksObject($data)
            : $data;
    }

    /**
     * @param array<array-key, array|string|int|float|bool|\ArrayObject|null> $data
     *
     * @return array<array-key, array|string|int|float|bool|\ArrayObject|stdClass|null>
     */
    private function withEmptyWebhooksObject(array $data): array
    {
        $data['webhooks'] = new stdClass();

        return $data;
    }
}
