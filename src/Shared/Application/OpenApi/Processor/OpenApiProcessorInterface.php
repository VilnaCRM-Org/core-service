<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\OpenApi;

interface OpenApiProcessorInterface
{
    public function process(OpenApi $openApi): OpenApi;
}
