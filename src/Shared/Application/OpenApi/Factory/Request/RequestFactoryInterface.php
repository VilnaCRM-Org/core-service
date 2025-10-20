<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Request;

use ApiPlatform\OpenApi\Model\RequestBody;

interface RequestFactoryInterface
{
    public function getRequest(): RequestBody;
}
