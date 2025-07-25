<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\UriParameter;

use ApiPlatform\OpenApi\Model\Parameter;

interface UriParameterFactoryInterface
{
    public function getParameter(): Parameter;
}
