<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Response\Status;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Application\OpenApi\Builder\Parameter;
use App\Shared\Application\OpenApi\Builder\ResponseBuilder;
use App\Shared\Application\OpenApi\Factory\Response\CustomerStatus\CustomerStatusNotFoundResponseFactory;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class FactoryTest extends UnitTestCase
{
    public function testGetResponse(): void
    {
        $responseBuilder = $this->createMock(ResponseBuilder::class);
        $response = $this->createMock(Response::class);

        $responseBuilder->expects($this->once())
            ->method('build')
            ->with(
                'CustomerStatus not found',
                $this->isType('array'),
                []
            )
            ->willReturn($response);

        $factory = new CustomerStatusNotFoundResponseFactory($responseBuilder);

        $this->assertSame($response, $factory->getResponse());
    }

    public function testGetTypeParam(): void
    {
        $responseBuilder = $this->createMock(ResponseBuilder::class);
        $factory = new CustomerStatusNotFoundResponseFactory($responseBuilder);

        $param = $factory->getTypeParam();

        $this->assertInstanceOf(Parameter::class, $param);
        $this->assertEquals('type', $param->name);
        $this->assertEquals('string', $param->type);
        $this->assertEquals(
            'https://tools.ietf.org/html/rfc2616#section-10',
            $param->example
        );
    }

    public function testGetTitleParam(): void
    {
        $responseBuilder = $this->createMock(ResponseBuilder::class);
        $factory = new CustomerStatusNotFoundResponseFactory($responseBuilder);

        $param = $factory->getTitleParam();

        $this->assertInstanceOf(Parameter::class, $param);
        $this->assertEquals('title', $param->name);
        $this->assertEquals('string', $param->type);
        $this->assertEquals('An error occurred', $param->example);
    }

    public function testGetDetailParam(): void
    {
        $responseBuilder = $this->createMock(ResponseBuilder::class);
        $factory = new CustomerStatusNotFoundResponseFactory($responseBuilder);

        $param = $factory->getDetailParam();

        $this->assertInstanceOf(Parameter::class, $param);
        $this->assertEquals('detail', $param->name);
        $this->assertEquals('string', $param->type);
        $this->assertEquals('CustomerStatus not found', $param->example);
    }

    public function testGetStatusParam(): void
    {
        $responseBuilder = $this->createMock(ResponseBuilder::class);
        $factory = new CustomerStatusNotFoundResponseFactory($responseBuilder);

        $param = $factory->getStatusParam();

        $this->assertInstanceOf(Parameter::class, $param);
        $this->assertEquals('status', $param->name);
        $this->assertEquals('integer', $param->type);
        $this->assertEquals(HttpResponse::HTTP_NOT_FOUND, $param->example);
    }
}
