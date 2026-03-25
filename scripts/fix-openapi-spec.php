<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Shared\Application\OpenApi\Processor\OpenApiFixer;

$specFile = $argv[1] ?? (__DIR__ . '/../.github/openapi-spec/spec.yaml');

try {
    $fixer = new OpenApiFixer($specFile);
    $fixer->run();
} catch (\RuntimeException $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
