<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Serializer;

use ApiPlatform\OpenApi\Model\Parameter;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class ParameterNormalizer implements NormalizerInterface
{
    public function __construct(
        private readonly NormalizerInterface $decorated
    ) {
    }

    /**
     * @param Parameter $object
     * @param array<string, bool|int|string> $context
     */
    public function normalize(
        mixed $object,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|\ArrayObject|null {
        $data = $this->decorated->normalize($object, $format, $context);

        if ($this->shouldSkipProcessing($object, $data)) {
            return $data;
        }

        // Remove allowEmptyValue and allowReserved for path parameters as they're only valid for query parameters
        if ($object->getIn() === 'path') {
            unset($data['allowEmptyValue'], $data['allowReserved']);
        }

        return $data;
    }

    /**
     * @param object $data
     * @param array<string, bool|int|string> $context
     */
    public function supportsNormalization(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): bool {
        return $data instanceof Parameter;
    }

    /**
     * @return array<string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            Parameter::class => true,
        ];
    }

    private function shouldSkipProcessing(
        object $object,
        array|string|int|float|bool|\ArrayObject|null $data
    ): bool {
        if (!$object instanceof Parameter) {
            return true;
        }

        return !is_array($data);
    }
}
