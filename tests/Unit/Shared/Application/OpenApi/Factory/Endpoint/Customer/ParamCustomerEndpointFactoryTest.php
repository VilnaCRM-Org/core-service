<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Endpoint\Customer;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Core\Customer\Application\OpenApi\Endpoint\Customer\ParamFactory;
use App\Core\Customer\Application\OpenApi\Request\Customer\CreateFactory;
use App\Core\Customer\Application\OpenApi\Request\Customer\UpdateFactory;
use App\Core\Customer\Application\OpenApi\Response\Customer\DeletedFactory;
use App\Core\Customer\Application\OpenApi\Response\Customer\NotFoundFactory;
use App\Core\Customer\Application\OpenApi\UriParameter\CustomerFactory;
use App\Shared\Application\OpenApi\Factory\Response\BadRequestResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ForbiddenResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\InternalErrorFactory;
use App\Shared\Application\OpenApi\Factory\Response\UnauthorizedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ValidationErrorFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class ParamCustomerEndpointFactoryTest extends TestCase
{
    private CustomerFactory $parameterFactory;
    private UpdateFactory $updateCustomerRequestFactory;
    private ValidationErrorFactory $validationErrorFactory;
    private BadRequestResponseFactory $badRequestResponseFactory;
    private NotFoundFactory $customerNotFoundFactory;
    private DeletedFactory $deletedResponseFactory;
    private CreateFactory $replaceCustomerRequestFactory;
    private InternalErrorFactory $internalErrorFactory;
    private ForbiddenResponseFactory $forbiddenResponseFactory;
    private UnauthorizedResponseFactory $unauthorizedResponseFactory;
    private Parameter $ulidParam;
    private RequestBody $updateCustomerRequest;
    private RequestBody $replaceCustomerRequest;
    private Response $validResponse;
    private Response $badRequestResponse;
    private Response $customerNotFoundResponse;
    private Response $customerDeletedResponse;
    private Response $internalResponse;
    private Response $forbiddenResponse;
    private Response $unauthorizedResponse;
    private OpenApi $openApi;
    private Paths $paths;
    private PathItem $pathItem;
    private Operation $operationPut;
    private Operation $operationPatch;
    private Operation $operationGet;
    private Operation $operationDelete;

    protected function setUp(): void
    {
        $this->setupFactoryMocks();
        $this->setupResponseMocks();
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
        $this->parameterFactory = $this
            ->createMock(CustomerFactory::class);
        $this->updateCustomerRequestFactory = $this
            ->createMock(UpdateFactory::class);
        $this->validationErrorFactory = $this
            ->createMock(ValidationErrorFactory::class);
        $this->badRequestResponseFactory = $this
            ->createMock(BadRequestResponseFactory::class);
        $this->customerNotFoundFactory = $this
            ->createMock(NotFoundFactory::class);
        $this->deletedResponseFactory = $this
            ->createMock(DeletedFactory::class);
        $this->replaceCustomerRequestFactory = $this
            ->createMock(CreateFactory::class);
        $this->internalErrorFactory = $this
            ->createMock(InternalErrorFactory::class);
        $this->forbiddenResponseFactory = $this
            ->createMock(ForbiddenResponseFactory::class);
        $this->unauthorizedResponseFactory = $this
            ->createMock(UnauthorizedResponseFactory::class);
    }

    private function setupResponseMocks(): void
    {
        $this->setupRequestMocks();
        $this->setupApiResponseMocks();
    }

    private function setupRequestMocks(): void
    {
        $this->ulidParam = $this
            ->createMock(Parameter::class);
        $this->updateCustomerRequest = $this
            ->createMock(RequestBody::class);
        $this->replaceCustomerRequest = $this
            ->createMock(RequestBody::class);
    }

    private function setupApiResponseMocks(): void
    {
        $this->validResponse = $this->createResponseMock();
        $this->badRequestResponse = $this->createResponseMock();
        $this->customerNotFoundResponse = $this->createResponseMock();
        $this->customerDeletedResponse = $this->createResponseMock();
        $this->internalResponse = $this->createResponseMock();
        $this->forbiddenResponse = $this->createResponseMock();
        $this->unauthorizedResponse = $this->createResponseMock();
    }

    private function createResponseMock(): Response
    {
        return $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function setupOpenApiMocks(): void
    {
        $this->openApi = $this->createMock(OpenApi::class);
        $this->paths = $this->createMock(Paths::class);
        $this->pathItem = $this->createMock(PathItem::class);
        $this->operationPut = $this
            ->createMock(Operation::class);
        $this->operationPatch = $this
            ->createMock(Operation::class);
        $this->operationGet = $this
            ->createMock(Operation::class);
        $this->operationDelete = $this
            ->createMock(Operation::class);
    }

    private function setupFactoryReturnValues(): void
    {
        $this->parameterFactory->method('getParameter')
            ->willReturn($this->ulidParam);
        $this->updateCustomerRequestFactory->method('getRequest')
            ->willReturn($this->updateCustomerRequest);
        $this->validationErrorFactory->method('getResponse')
            ->willReturn($this->validResponse);
        $this->badRequestResponseFactory->method('getResponse')
            ->willReturn($this->badRequestResponse);
        $this->customerNotFoundFactory->method('getResponse')
            ->willReturn($this->customerNotFoundResponse);
        $this->deletedResponseFactory->method('getResponse')
            ->willReturn($this->customerDeletedResponse);
        $this->replaceCustomerRequestFactory->method('getRequest')
            ->willReturn($this->replaceCustomerRequest);
        $this->internalErrorFactory->method('getResponse')
            ->willReturn($this->internalResponse);
        $this->forbiddenResponseFactory->method('getResponse')
            ->willReturn($this->forbiddenResponse);
        $this->unauthorizedResponseFactory->method('getResponse')
            ->willReturn($this->unauthorizedResponse);
    }

    private function createFactory(): ParamFactory
    {
        return new ParamFactory(
            $this->parameterFactory,
            $this->updateCustomerRequestFactory,
            $this->validationErrorFactory,
            $this->badRequestResponseFactory,
            $this->customerNotFoundFactory,
            $this->deletedResponseFactory,
            $this->replaceCustomerRequestFactory,
            $this->internalErrorFactory,
            $this->forbiddenResponseFactory,
            $this->unauthorizedResponseFactory
        );
    }

    private function setExpectations(): void
    {
        $this->setupOpenApiAndPathsExpectations();
        $this->setupOperationResponses();

        $this->setupPutOperationExpectations();
        $this->setupPatchOperationExpectations();
        $this->setupGetOperationExpectations();
        $this->setupDeleteOperationExpectations();

        $this->setupPathsAddPathExpectations();
    }

    private function setupOpenApiAndPathsExpectations(): void
    {
        $this->openApi->method('getPaths')->willReturn($this->paths);
        $this->paths->expects($this->exactly(4))
            ->method('getPath')
            ->with('/api/customers/{ulid}')
            ->willReturn($this->pathItem);

        $this->pathItem->expects($this->once())
            ->method('getPut')->willReturn($this->operationPut);
        $this->pathItem->expects($this->once())
            ->method('getPatch')->willReturn($this->operationPatch);
        $this->pathItem->expects($this->once())
            ->method('getGet')->willReturn($this->operationGet);
        $this->pathItem->expects($this->once())
            ->method('getDelete')->willReturn($this->operationDelete);
    }

    private function setupOperationResponses(): void
    {
        $this->operationPut->expects($this->once())
            ->method('getResponses')->willReturn([]);
        $this->operationPatch->expects($this->once())
            ->method('getResponses')->willReturn([]);
        $this->operationGet->expects($this->once())
            ->method('getResponses')->willReturn([]);
    }

    /**
     * @return array<int, Response>
     */
    private function getUpdateResponses(): array
    {
        return [
            HttpResponse::HTTP_BAD_REQUEST => $this->badRequestResponse,
            HttpResponse::HTTP_UNAUTHORIZED => $this->unauthorizedResponse,
            HttpResponse::HTTP_FORBIDDEN => $this->forbiddenResponse,
            HttpResponse::HTTP_NOT_FOUND => $this->customerNotFoundResponse,
            HttpResponse::HTTP_UNPROCESSABLE_ENTITY => $this->validResponse,
            HttpResponse::HTTP_INTERNAL_SERVER_ERROR => $this->internalResponse,
        ];
    }

    /**
     * @return array<int, Response>
     */
    private function getDeleteResponses(): array
    {
        return [
            HttpResponse::HTTP_NO_CONTENT => $this->customerDeletedResponse,
            HttpResponse::HTTP_UNAUTHORIZED => $this->unauthorizedResponse,
            HttpResponse::HTTP_FORBIDDEN => $this->forbiddenResponse,
            HttpResponse::HTTP_NOT_FOUND => $this->customerNotFoundResponse,
            HttpResponse::HTTP_INTERNAL_SERVER_ERROR => $this->internalResponse,
        ];
    }

    /**
     * @return array<int, Response>
     */
    private function getGetResponses(): array
    {
        return [
            HttpResponse::HTTP_UNAUTHORIZED => $this->unauthorizedResponse,
            HttpResponse::HTTP_FORBIDDEN => $this->forbiddenResponse,
            HttpResponse::HTTP_NOT_FOUND => $this->customerNotFoundResponse,
            HttpResponse::HTTP_INTERNAL_SERVER_ERROR => $this->internalResponse,
        ];
    }

    private function setupPutOperationExpectations(): void
    {
        $this->operationPut->expects($this->once())
            ->method('withParameters')
            ->with([$this->ulidParam])->willReturnSelf();
        $this->operationPut->expects($this->once())
            ->method('withResponses')
            ->with($this->getUpdateResponses())->willReturnSelf();
        $this->operationPut->expects($this->once())
            ->method('withRequestBody')
            ->with($this->replaceCustomerRequest)->willReturnSelf();
        $this->pathItem->expects($this->once())
            ->method('withPut')
            ->with($this->operationPut)->willReturnSelf();
    }

    private function setupPatchOperationExpectations(): void
    {
        $this->operationPatch->expects($this->once())
            ->method('withParameters')
            ->with([$this->ulidParam])->willReturnSelf();
        $this->operationPatch->expects($this->once())
            ->method('withRequestBody')
            ->with($this->updateCustomerRequest)->willReturnSelf();
        $this->operationPatch->expects($this->once())
            ->method('withResponses')
            ->with($this->getUpdateResponses())->willReturnSelf();
        $this->pathItem->expects($this->once())
            ->method('withPatch')
            ->with($this->operationPatch)->willReturnSelf();
    }

    private function setupGetOperationExpectations(): void
    {
        $this->operationGet->expects($this->once())
            ->method('withParameters')
            ->with([$this->ulidParam])->willReturnSelf();
        $this->operationGet->expects($this->once())
            ->method('withResponses')
            ->with($this->getGetResponses())->willReturnSelf();
        $this->pathItem->expects($this->once())
            ->method('withGet')
            ->with($this->operationGet)->willReturnSelf();
    }

    private function setupDeleteOperationExpectations(): void
    {
        $this->operationDelete->expects($this->once())
            ->method('withParameters')
            ->with([$this->ulidParam])->willReturnSelf();
        $this->operationDelete->expects($this->once())
            ->method('withResponses')
            ->with($this->getDeleteResponses())->willReturnSelf();
        $this->pathItem->expects($this->once())
            ->method('withDelete')
            ->with($this->operationDelete)->willReturnSelf();
    }

    private function setupPathsAddPathExpectations(): void
    {
        $this->paths->expects($this->exactly(4))
            ->method('addPath')
            ->with('/api/customers/{ulid}', $this->pathItem);
    }
}
