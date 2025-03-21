<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Endpoint\CustomerStatus;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Endpoint\CustomerStatus\ParamCustomerStatusEndpointFactory;
use App\Shared\Application\OpenApi\Factory\Request\Status\StatusCreateFactory;
use App\Shared\Application\OpenApi\Factory\Request\Status\StatusUpdateFactory;
use App\Shared\Application\OpenApi\Factory\Response\BadRequestResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ForbiddenResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\InternalErrorFactory;
use App\Shared\Application\OpenApi\Factory\Response\Status\DeletedFactory;
use App\Shared\Application\OpenApi\Factory\Response\Status\NotFoundFactory;
use App\Shared\Application\OpenApi\Factory\Response\UnauthorizedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ValidationErrorFactory;
use App\Shared\Application\OpenApi\Factory\UriParameter\CustomerStatusFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class ParamCustomerStatusEndpointFactoryTest extends TestCase
{
    private CustomerStatusFactory $parameterFactory;
    private StatusUpdateFactory $updateFactory;
    private ValidationErrorFactory $validationErrorFactory;
    private BadRequestResponseFactory $badRequestResponseFactory;
    private NotFoundFactory $notFoundFactory;
    private DeletedFactory $deletedFactory;
    private StatusCreateFactory $replaceFactory;
    private InternalErrorFactory $internalErrorFactory;
    private ForbiddenResponseFactory $forbiddenResponseFactory;
    private UnauthorizedResponseFactory $unauthorizedResponseFactory;

    private Parameter $ulidParam;
    private RequestBody $updateRequest;
    private RequestBody $replaceRequest;
    private Response $validResponse;
    private Response $badResponse;
    private Response $notFoundResponse;
    private Response $deletedResponse;
    private Response $internalResponse;
    private Response $forbiddenResponse;
    private Response $unauthorizedResponse;

    private OpenApi $openApi;
    private Paths $paths;
    private PathItem $pathItem;
    private Operation $operationPatch;
    private Operation $operationPut;
    private Operation $operationGet;
    private Operation $operationDelete;

    protected function setUp(): void
    {
        $this->setupFactoryMocks();
        $this->setupRequestAndResponseMocks();
        $this->setupOpenApiMocks();
    }

    public function testCreateEndpoint(): void
    {
        $this->setupFactoryReturnValues();
        $this->setExpectations();

        $factory = $this->createFactory();
        $factory->createEndpoint($this->openApi);
    }

    private function setupFactoryMocks(): void
    {
        $this->parameterFactory = $this->createMock(CustomerStatusFactory::class);
        $this->updateFactory = $this->createMock(StatusUpdateFactory::class);
        $this->validationErrorFactory = $this->createMock(ValidationErrorFactory::class);
        $this->badRequestResponseFactory = $this->createMock(BadRequestResponseFactory::class);
        $this->notFoundFactory = $this->createMock(NotFoundFactory::class);
        $this->deletedFactory = $this->createMock(DeletedFactory::class);
        $this->replaceFactory = $this->createMock(StatusCreateFactory::class);
        $this->internalErrorFactory = $this->createMock(InternalErrorFactory::class);
        $this->forbiddenResponseFactory = $this->createMock(ForbiddenResponseFactory::class);
        $this->unauthorizedResponseFactory = $this->createMock(UnauthorizedResponseFactory::class);
    }

    private function setupRequestAndResponseMocks(): void
    {
        $this->ulidParam = $this->createMock(Parameter::class);
        $this->updateRequest = $this->createMock(RequestBody::class);
        $this->replaceRequest = $this->createMock(RequestBody::class);
        $this->validResponse = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->getMock();
        $this->badResponse = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->getMock();
        $this->notFoundResponse = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->getMock();
        $this->deletedResponse = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->getMock();
        $this->internalResponse = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->getMock();
        $this->forbiddenResponse = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->getMock();
        $this->unauthorizedResponse = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->getMock();
    }

    private function setupOpenApiMocks(): void
    {
        $this->openApi = $this->createMock(OpenApi::class);
        $this->paths = $this->createMock(Paths::class);
        $this->pathItem = $this->createMock(PathItem::class);
        $this->operationPatch = $this->createMock(Operation::class);
        $this->operationPut = $this->createMock(Operation::class);
        $this->operationGet = $this->createMock(Operation::class);
        $this->operationDelete = $this->createMock(Operation::class);
    }

    private function setupFactoryReturnValues(): void
    {
        $this->parameterFactory->method('getParameter')->willReturn($this->ulidParam);
        $this->updateFactory->method('getRequest')->willReturn($this->updateRequest);
        $this->validationErrorFactory->method('getResponse')->willReturn($this->validResponse);
        $this->badRequestResponseFactory->method('getResponse')->willReturn($this->badResponse);
        $this->notFoundFactory->method('getResponse')->willReturn($this->notFoundResponse);
        $this->deletedFactory->method('getResponse')->willReturn($this->deletedResponse);
        $this->replaceFactory->method('getRequest')->willReturn($this->replaceRequest);
        $this->internalErrorFactory->method('getResponse')->willReturn($this->internalResponse);
        $this->forbiddenResponseFactory->method('getResponse')->willReturn($this->forbiddenResponse);
        $this->unauthorizedResponseFactory->method('getResponse')->willReturn($this->unauthorizedResponse);
    }

    private function createFactory(): ParamCustomerStatusEndpointFactory
    {
        return new ParamCustomerStatusEndpointFactory(
            $this->parameterFactory,
            $this->updateFactory,
            $this->validationErrorFactory,
            $this->badRequestResponseFactory,
            $this->notFoundFactory,
            $this->deletedFactory,
            $this->replaceFactory,
            $this->internalErrorFactory,
            $this->forbiddenResponseFactory,
            $this->unauthorizedResponseFactory
        );
    }

    private function setExpectations(): void
    {
        $this->openApi->method('getPaths')->willReturn($this->paths);
        $this->paths->expects($this->exactly(4))
            ->method('getPath')
            ->with('/api/customer_statuses/{ulid}')
            ->willReturn($this->pathItem);

        $this->setupPatchOperation();
        $this->setupPutOperation();
        $this->setupGetOperation();
        $this->setupDeleteOperation();

        $this->paths->expects($this->atLeastOnce())
            ->method('addPath')
            ->with('/api/customer_statuses/{ulid}', $this->pathItem);
    }

    private function setupPatchOperation(): void
    {
        $this->pathItem->expects($this->once())->method('getPatch')->willReturn($this->operationPatch);
        $this->operationPatch->expects($this->once())->method('getResponses')->willReturn([]);
        $this->operationPatch->expects($this->once())
            ->method('withParameters')->with([$this->ulidParam])->willReturnSelf();
        $this->operationPatch->expects($this->once())
            ->method('withRequestBody')->with($this->updateRequest)->willReturnSelf();
        $expectedUpdate = $this->getUpdateExpectedResponses();
        $this->operationPatch->expects($this->once())
            ->method('withResponses')->with($expectedUpdate)->willReturnSelf();
        $this->pathItem->expects($this->once())
            ->method('withPatch')->with($this->operationPatch)->willReturnSelf();
    }

    private function setupPutOperation(): void
    {
        $this->pathItem->expects($this->once())->method('getPut')->willReturn($this->operationPut);
        $this->operationPut->expects($this->once())->method('getResponses')->willReturn([]);
        $this->operationPut->expects($this->once())
            ->method('withParameters')->with([$this->ulidParam])->willReturnSelf();
        $expectedUpdate = $this->getUpdateExpectedResponses();
        $this->operationPut->expects($this->once())
            ->method('withResponses')->with($expectedUpdate)->willReturnSelf();
        $this->operationPut->expects($this->once())
            ->method('withRequestBody')->with($this->replaceRequest)->willReturnSelf();
        $this->pathItem->expects($this->once())
            ->method('withPut')->with($this->operationPut)->willReturnSelf();
    }

    private function setupGetOperation(): void
    {
        $this->pathItem->expects($this->once())->method('getGet')->willReturn($this->operationGet);
        $this->operationGet->expects($this->once())->method('getResponses')->willReturn([]);
        $expectedGet = $this->getGetExpectedResponses();
        $this->operationGet->expects($this->once())
            ->method('withParameters')->with([$this->ulidParam])->willReturnSelf();
        $this->operationGet->expects($this->once())
            ->method('withResponses')->with($expectedGet)->willReturnSelf();
        $this->pathItem->expects($this->once())
            ->method('withGet')->with($this->operationGet)->willReturnSelf();
    }

    private function setupDeleteOperation(): void
    {
        $this->pathItem->expects($this->once())->method('getDelete')->willReturn($this->operationDelete);
        $this->operationDelete->expects($this->once())
            ->method('withParameters')->with([$this->ulidParam])->willReturnSelf();
        $expectedDelete = $this->getDeleteExpectedResponses();
        $this->operationDelete->expects($this->once())
            ->method('withResponses')->with($expectedDelete)->willReturnSelf();
        $this->pathItem->expects($this->once())
            ->method('withDelete')->with($this->operationDelete)->willReturnSelf();
    }

    private function getUpdateExpectedResponses(): array
    {
        return [
            HttpResponse::HTTP_BAD_REQUEST => $this->badResponse,
            HttpResponse::HTTP_UNAUTHORIZED => $this->unauthorizedResponse,
            HttpResponse::HTTP_FORBIDDEN => $this->forbiddenResponse,
            HttpResponse::HTTP_NOT_FOUND => $this->notFoundResponse,
            HttpResponse::HTTP_UNPROCESSABLE_ENTITY => $this->validResponse,
            HttpResponse::HTTP_INTERNAL_SERVER_ERROR => $this->internalResponse,
        ];
    }

    private function getGetExpectedResponses(): array
    {
        return [
            HttpResponse::HTTP_UNAUTHORIZED => $this->unauthorizedResponse,
            HttpResponse::HTTP_FORBIDDEN => $this->forbiddenResponse,
            HttpResponse::HTTP_NOT_FOUND => $this->notFoundResponse,
            HttpResponse::HTTP_INTERNAL_SERVER_ERROR => $this->internalResponse,
        ];
    }

    private function getDeleteExpectedResponses(): array
    {
        return [
            HttpResponse::HTTP_NO_CONTENT => $this->deletedResponse,
            HttpResponse::HTTP_UNAUTHORIZED => $this->unauthorizedResponse,
            HttpResponse::HTTP_FORBIDDEN => $this->forbiddenResponse,
            HttpResponse::HTTP_NOT_FOUND => $this->notFoundResponse,
            HttpResponse::HTTP_INTERNAL_SERVER_ERROR => $this->internalResponse,
        ];
    }
}
