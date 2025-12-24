<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Validates AWS CloudWatch EMF dimension values.
 *
 * AWS EMF dimension value constraints:
 * - 1-1024 characters
 * - ASCII only (no Unicode)
 * - No ASCII control characters
 * - Must contain at least one non-whitespace character
 */
#[\Attribute]
final class EmfValue extends Constraint
{
    public const int MAX_LENGTH = 1024;

    public string $emptyMessage = 'emf.value.empty';
    public string $tooLongMessage = 'emf.value.too_long';
    public string $nonAsciiMessage = 'emf.value.non_ascii';
    public string $controlCharsMessage = 'emf.value.control_chars';

    public function __construct(
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct([], $groups, $payload);
    }
}
