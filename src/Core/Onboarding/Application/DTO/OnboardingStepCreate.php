<?php

declare(strict_types=1);

namespace App\Core\Onboarding\Application\DTO;

final class OnboardingStepCreate
{
    public ?string $code = null;

    public ?string $label = null;

    public ?int $position = null;

    public ?bool $enabled = null;
}
