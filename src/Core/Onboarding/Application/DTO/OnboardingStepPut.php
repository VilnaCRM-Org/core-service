<?php

declare(strict_types=1);

namespace App\Core\Onboarding\Application\DTO;

final class OnboardingStepPut
{
    public string $code;

    public string $label;

    public int $position;

    public bool $enabled;
}
