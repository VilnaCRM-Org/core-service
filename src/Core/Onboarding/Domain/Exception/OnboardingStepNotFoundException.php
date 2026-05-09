<?php

declare(strict_types=1);

namespace App\Core\Onboarding\Domain\Exception;

use RuntimeException;

final class OnboardingStepNotFoundException extends RuntimeException
{
    public static function withIri(string $iri): self
    {
        return new self(sprintf('Onboarding step "%s" was not found.', $iri));
    }
}
