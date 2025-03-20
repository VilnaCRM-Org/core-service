<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Response\Customer;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Application\OpenApi\Builder\ResponseBuilder;
use App\Shared\Application\OpenApi\Builder\Parameter;
use App\Shared\Application\OpenApi\Factory\Response\Customer\NotFoundFactory;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class NotFoundFactoryTest extends UnitTestCase
{
    public function testGetResponse(): void
    {
        $responseBuilderMock = $this->createMock(ResponseBuilder::class);

        $expectedParameters = [
            $this->getTypeParam(),
            $this->getTitleParam(),
            $this->getDetailParam(),
            $this->getStatusParam(),
        ];

        $responseBuilderMock->expects($this->once())
            ->method('build')
            ->with(
                'Customer not found',
                $expectedParameters,
                []
            )
            ->willReturn($this->createStub(Response::class));

        $factory = new NotFoundFactory($responseBuilderMock);
        $response = $factory->getResponse();

        $this->assertInstanceOf(Response::class, $response);
    }

    private function getTypeParam(): Parameter
    {
        return new Parameter(
            'type',
            'string',
            'https://tools.ietf.org/html/rfc2616#section-10'
        );
    }

    private function getTitleParam(): Parameter
    {
        return new Parameter(
            'title',
            'string',
            'An error occurred'
        );
    }

    private function getDetailParam(): Parameter
    {
        return new Parameter(
            'detail',
            'string',
            'Customer not found'
        );
    }

    private function getStatusParam(): Parameter
    {
        return new Parameter(
            'status',
            'integer',
            HttpResponse::HTTP_NOT_FOUND
        );
    }
}
