<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Endpoint\Type;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Core\Customer\Application\OpenApi\Endpoint\Type\TypeParamFactory;
use App\Core\Customer\Application\OpenApi\Request\Type\TypeCreateFactory;
use App\Core\Customer\Application\OpenApi\Request\Type\TypeUpdateFactory;
use App\Core\Customer\Application\OpenApi\Response\Type\DeletedFactory;
use App\Core\Customer\Application\OpenApi\Response\Type\NotFoundFactory;
use App\Core\Customer\Application\OpenApi\UriParameter\CustomerTypeFactory;
use App\Shared\Application\OpenApi\Factory\Response\BadRequestResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ForbiddenResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\InternalErrorFactory;
use App\Shared\Application\OpenApi\Factory\Response\UnauthorizedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ValidationErrorFactory;
use App\Tests\Unit\UnitTestCase;

final class ParamCustomerTypeEndpointFactoryTest extends UnitTestCase
{
    private CustomerTypeFactory $parameterFactory;
    private TypeUpdateFactory $updateFactory;
    private ValidationErrorFactory $validationErrorFactory;
    private BadRequestResponseFactory $badRequestResponseFactory;
    private NotFoundFactory $customerTypeNotFoundFactory;
    private DeletedFactory $deletedResponseFactory;
    private TypeCreateFactory $replaceCustomerRequestFactory;
    private InternalErrorFactory $internalErrorFactory;
    private ForbiddenResponseFactory $forbiddenResponseFactory;
    private UnauthorizedResponseFactory $unauthorizedResponseFactory;

    private Parameter $ulidWithExamplePathParam;
    private RequestBody $updateCustomerTypeRequest;
    private Response $validResponse;
    private Response $badRequestResponse;
    private Response $notFoundResponse;
    private Response $typeDeletedResponse;
    private RequestBody $replaceCustomerTypeRequest;
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
        $this->parameterFactory = $this->createMock(CustomerTypeFactory::class);
        $this->updateFactory = $this->createMock(TypeUpdateFactory::class);
        $this->validationErrorFactory = $this->createMock(ValidationErrorFactory::class);
        $this->badRequestResponseFactory = $this->createMock(BadRequestResponseFactory::class);
        $this->customerTypeNotFoundFactory = $this->createMock(NotFoundFactory::class);
        $this->deletedResponseFactory = $this->createMock(DeletedFactory::class);
        $this->replaceCustomerRequestFactory = $this->createMock(TypeCreateFactory::class);
        $this->internalErrorFactory = $this->createMock(InternalErrorFactory::class);
        $this->forbiddenResponseFactory = $this->createMock(ForbiddenResponseFactory::class);
        $this->unauthorizedResponseFactory = $this->createMock(UnauthorizedResponseFactory::class);
    }

    private function setupRequestAndResponseMocks(): void
    {
        $this->ulidWithExamplePathParam = $this->createMock(Parameter::class);
        $this->updateCustomerTypeRequest = $this->createMock(RequestBody::class);
        $this->validResponse = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->getMock();
        $this->badRequestResponse = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->getMock();
        $this->notFoundResponse = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->getMock();
        $this->typeDeletedResponse = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->getMock();
        $this->replaceCustomerTypeRequest = $this->createMock(RequestBody::class);
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
        $this->parameterFactory->method('getParameter')->willReturn($this->ulidWithExamplePathParam);
        $this->updateFactory->method('getRequest')->willReturn($this->updateCustomerTypeRequest);
        $this->validationErrorFactory->method('getResponse')->willReturn($this->validResponse);
        $this->badRequestResponseFactory->method('getResponse')->willReturn($this->badRequestResponse);
        $this->customerTypeNotFoundFactory->method('getResponse')->willReturn($this->notFoundResponse);
        $this->deletedResponseFactory->method('getResponse')->willReturn($this->typeDeletedResponse);
        $this->replaceCustomerRequestFactory->method('getRequest')->willReturn($this->replaceCustomerTypeRequest);
        $this->internalErrorFactory->method('getResponse')->willReturn($this->internalResponse);
        $this->forbiddenResponseFactory->method('getResponse')->willReturn($this->forbiddenResponse);
        $this->unauthorizedResponseFactory->method('getResponse')->willReturn($this->unauthorizedResponse);
    }

    private function createFactory(): TypeParamFactory
    {
        return new TypeParamFactory(
            $this->parameterFactory,
            $this->updateFactory,
            $this->validationErrorFactory,
            $this->badRequestResponseFactory,
            $this->customerTypeNotFoundFactory,
            $this->deletedResponseFactory,
            $this->replaceCustomerRequestFactory,
            $this->internalErrorFactory,
            $this->forbiddenResponseFactory,
            $this->unauthorizedResponseFactory
        );
    }

    private function setExpectations(): void
    {
        $this->openApi->method('getPaths')->willReturn($this->paths);
        $this->paths->method('getPath')->with('/api/customer_types/{ulid}')->willReturn($this->pathItem);

        // Setup all operation mocks
        $this->pathItem->method('getPatch')->willReturn($this->operationPatch);
        $this->pathItem->method('getPut')->willReturn($this->operationPut);
        $this->pathItem->method('getGet')->willReturn($this->operationGet);
        $this->pathItem->method('getDelete')->willReturn($this->operationDelete);

        // Setup operation responses
        $this->operationPatch->method('getResponses')->willReturn([]);
        $this->operationPut->method('getResponses')->willReturn([]);
        $this->operationGet->method('getResponses')->willReturn([]);

        // Setup method chaining
        $this->operationPatch->method('withParameters')->willReturnSelf();
        $this->operationPatch->method('withRequestBody')->willReturnSelf();
        $this->operationPatch->method('withResponses')->willReturnSelf();

        $this->operationPut->method('withParameters')->willReturnSelf();
        $this->operationPut->method('withResponses')->willReturnSelf();
        $this->operationPut->method('withRequestBody')->willReturnSelf();

        $this->operationGet->method('withParameters')->willReturnSelf();
        $this->operationGet->method('withResponses')->willReturnSelf();

        $this->operationDelete->method('withParameters')->willReturnSelf();
        $this->operationDelete->method('withResponses')->willReturnSelf();

        $this->pathItem->method('withPatch')->willReturnSelf();
        $this->pathItem->method('withPut')->willReturnSelf();
        $this->pathItem->method('withGet')->willReturnSelf();
        $this->pathItem->method('withDelete')->willReturnSelf();

        $this->paths->expects($this->exactly(4))->method('addPath');
    }
}
