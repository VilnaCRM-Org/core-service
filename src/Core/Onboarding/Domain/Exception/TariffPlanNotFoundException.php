<?php

declare(strict_types=1);

namespace App\Core\Onboarding\Domain\Exception;

use RuntimeException;

final class TariffPlanNotFoundException extends RuntimeException
{
    public static function withIri(string $iri): self
    {
        return new self(sprintf('Tariff plan "%s" was not found.', $iri));
    }
}
