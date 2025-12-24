<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Validates AWS CloudWatch EMF dimension keys.
 *
 * AWS EMF dimension key constraints:
 * - 1-255 characters
 * - ASCII only (no Unicode)
 * - No ASCII control characters
 * - Cannot start with colon (:)
 * - Must contain at least one non-whitespace character
 */
#[\Attribute]
final class EmfKey extends Constraint
{
    public const int MAX_LENGTH = 255;

    public string $emptyMessage = 'EMF dimension key must contain at least one non-whitespace character';
    public string $tooLongMessage = 'EMF dimension key must not exceed 255 characters';
    public string $nonAsciiMessage = 'EMF dimension key must contain only ASCII characters';
    public string $controlCharsMessage = 'EMF dimension key must not contain ASCII control characters';
    public string $startsWithColonMessage = 'EMF dimension key must not start with colon (:)';

    public function __construct(
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct([], $groups, $payload);
    }
}
