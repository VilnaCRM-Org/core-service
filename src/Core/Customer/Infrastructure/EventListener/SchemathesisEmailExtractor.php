<?php

declare(strict_types=1);

namespace App\Core\Customer\Infrastructure\EventListener;

use Symfony\Component\HttpFoundation\Request;

final class SchemathesisEmailExtractor
{
    public function __construct(
        private readonly SchemathesisPayloadDecoder $payloadDecoder,
        private readonly SchemathesisSingleCustomerEmailExtractor $singleCustomerExtractor
    ) {
    }

    /**
     * @return list<string>
     */
    public function extract(Request $request): array
    {
        $payload = $this->payloadDecoder->decode($request);

        return $this->singleCustomerExtractor->extract($payload);
    }
}
