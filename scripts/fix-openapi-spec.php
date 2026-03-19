<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Shared\Application\OpenApi\Processor\OpenApiFixer;

$specFile = '.github/openapi-spec/spec.yaml';
$fixer = new OpenApiFixer($specFile);
$fixer->run();
