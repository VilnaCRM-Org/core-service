<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Response\Status;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Application\OpenApi\Builder\ResponseBuilder;
use App\Shared\Application\OpenApi\Factory\Response\CustomerStatus\StatusNotFoundResponseFactory;
use App\Shared\Application\OpenApi\ValueObject\Parameter;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class NotFoundTest extends UnitTestCase
{
    public function testGetResponse(): void
    {
        $responseBuilder = $this->createMock(ResponseBuilder::class);
        $response = $this->createMock(Response::class);
        $expectedParams = $this->buildExpectedParameters();

        $responseBuilder->expects($this->once())
            ->method('build')
            ->with('CustomerStatus not found', $expectedParams, [])
            ->willReturn($response);

        $factory = new StatusNotFoundResponseFactory($responseBuilder);

        $this->assertSame($response, $factory->getResponse());
    }

    public function testGetTypeParam(): void
    {
        $responseBuilder = $this->createMock(ResponseBuilder::class);
        $factory = new StatusNotFoundResponseFactory($responseBuilder);

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
        $factory = new StatusNotFoundResponseFactory($responseBuilder);

        $param = $factory->getTitleParam();

        $this->assertInstanceOf(Parameter::class, $param);
        $this->assertEquals('title', $param->name);
        $this->assertEquals('string', $param->type);
        $this->assertEquals('An error occurred', $param->example);
    }

    public function testGetDetailParam(): void
    {
        $responseBuilder = $this->createMock(ResponseBuilder::class);
        $factory = new StatusNotFoundResponseFactory($responseBuilder);

        $param = $factory->getDetailParam();

        $this->assertInstanceOf(Parameter::class, $param);
        $this->assertEquals('detail', $param->name);
        $this->assertEquals('string', $param->type);
        $this->assertEquals('CustomerStatus not found', $param->example);
    }

    public function testGetStatusParam(): void
    {
        $responseBuilder = $this->createMock(ResponseBuilder::class);
        $factory = new StatusNotFoundResponseFactory($responseBuilder);

        $param = $factory->getStatusParam();

        $this->assertInstanceOf(Parameter::class, $param);
        $this->assertEquals('status', $param->name);
        $this->assertEquals('integer', $param->type);
        $this->assertEquals(HttpResponse::HTTP_NOT_FOUND, $param->example);
    }

    /**
     * @return array<int, Parameter>
     */
    private function buildExpectedParameters(): array
    {
        return [
            new Parameter(
                'type',
                'string',
                'https://tools.ietf.org/html/rfc2616#section-10'
            ),
            new Parameter('title', 'string', 'An error occurred'),
            new Parameter('detail', 'string', 'CustomerStatus not found'),
            new Parameter('status', 'integer', HttpResponse::HTTP_NOT_FOUND),
        ];
    }
}
