<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Request\Status;

use ApiPlatform\OpenApi\Model\RequestBody;
use App\Shared\Application\OpenApi\Builder\RequestPatchBuilder;
use App\Shared\Application\OpenApi\Factory\Request\CustomerStatus\StatusUpdateRequestFactory;
use App\Tests\Unit\UnitTestCase;

final class UpdateTest extends UnitTestCase
{
    public function testGetRequest(): void
    {
        $requestPatchBuilder = $this->createMock(RequestPatchBuilder::class);
        $requestBody = $this->createMock(RequestBody::class);

        $requestPatchBuilder->expects($this->once())
            ->method('build')
            ->willReturn($requestBody);

        $factory = new StatusUpdateRequestFactory($requestPatchBuilder);

        $this->assertSame($requestBody, $factory->getRequest());
    }
}
