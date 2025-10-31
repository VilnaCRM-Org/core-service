<?php

declare(strict_types=1);

namespace App\Core\Customer\Infrastructure\EventListener;

use Symfony\Component\HttpFoundation\Request;

final class SchemathesisEmailExtractor
{
    private readonly SchemathesisCleanupEvaluator $evaluator;
    private readonly SchemathesisPayloadDecoder $payloadDecoder;
    private readonly SchemathesisSingleCustomerEmailExtractor $singleCustomerExtractor;

    public function __construct(
        SchemathesisCleanupEvaluator $evaluator,
        SchemathesisPayloadDecoder $payloadDecoder,
        SchemathesisSingleCustomerEmailExtractor $singleCustomerExtractor
    ) {
        $this->evaluator = $evaluator;
        $this->payloadDecoder = $payloadDecoder;
        $this->singleCustomerExtractor = $singleCustomerExtractor;
    }

    /**
     * @return list<string>
     */
    public function extract(Request $request): array
    {
        $payload = $this->payloadDecoder->decode($request);

        if ($payload === []) {
            return [];
        }

        return $this->extractFromPayload($payload);
    }

    /**
     * @param array<string, array|scalar|null> $payload
     *
     * @return array<string>
     */
    private function extractFromPayload(array $payload): array
    {
        return $this->singleCustomerExtractor->extract($payload);
    }
}
