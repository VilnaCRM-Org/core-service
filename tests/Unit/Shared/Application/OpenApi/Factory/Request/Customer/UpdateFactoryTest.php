<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Request\Customer;

use ApiPlatform\OpenApi\Model\RequestBody;
use App\Shared\Application\OpenApi\Builder\RequestPatchBuilder;
use App\Shared\Application\OpenApi\Factory\Request\Customer\UpCReq;
use App\Tests\Unit\UnitTestCase;

final class UpdateFactoryTest extends UnitTestCase
{
    public function testGetRequest(): void
    {
        $requestBuilderMock = $this->createMock(RequestPatchBuilder::class);

        $requestBuilderMock->expects($this->once())
            ->method('build')
            ->with($this->callback(static function ($params) {
                return is_array($params) && count($params) === 7;
            }))
            ->willReturn($this->createMock(RequestBody::class));

        $updateFactory = new UpCReq($requestBuilderMock);

        $requestBody = $updateFactory->getRequest();

        $this->assertInstanceOf(RequestBody::class, $requestBody);
    }
}
