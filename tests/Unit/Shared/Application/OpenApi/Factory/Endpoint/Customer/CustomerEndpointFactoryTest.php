<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Endpoint\Customer;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Core\Customer\Application\OpenApi\Endpoint\Customer\EndpointFactory;
use App\Core\Customer\Application\OpenApi\Request\Customer\CreateFactory;
use App\Shared\Application\OpenApi\Factory\Response\BadRequestResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ForbiddenResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\InternalErrorFactory;
use App\Shared\Application\OpenApi\Factory\Response\UnauthorizedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ValidationErrorFactory;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class CustomerEndpointFactoryTest extends UnitTestCase
{
    private CreateFactory $createFactory;
    private ValidationErrorFactory $validationErrorFactory;
    private BadRequestResponseFactory $badRequestResponseFactory;
    private InternalErrorFactory $internalErrorFactory;
    private ForbiddenResponseFactory $forbiddenResponseFactory;
    private UnauthorizedResponseFactory $unauthorizedResponseFactory;
    private OpenApi $openApi;
    private RequestBody $createCustomerRequest;
    private Response $validResponse;
    private Response $badRequestResponse;
    private Response $internalResponse;
    private Response $forbiddenResponse;
    private Response $unauthorizedResponse;
    private Paths $paths;
    private PathItem $pathItem;
    private Operation $operationPost;
    private Operation $operationGet;

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

        $factory = new EndpointFactory(
            $this->createFactory,
            $this->validationErrorFactory,
            $this->badRequestResponseFactory,
            $this->internalErrorFactory,
            $this->forbiddenResponseFactory,
            $this->unauthorizedResponseFactory
        );

        $factory->createEndpoint($this->openApi);
    }

    private function setupFactoryMocks(): void
    {
        $this->createFactory = $this
            ->createMock(CreateFactory::class);
        $this->validationErrorFactory = $this
            ->createMock(ValidationErrorFactory::class);
        $this->badRequestResponseFactory = $this
            ->createMock(BadRequestResponseFactory::class);
        $this->internalErrorFactory = $this
            ->createMock(InternalErrorFactory::class);
        $this->forbiddenResponseFactory = $this
            ->createMock(ForbiddenResponseFactory::class);
        $this->unauthorizedResponseFactory = $this
            ->createMock(UnauthorizedResponseFactory::class);
    }

    private function setupRequestAndResponseMocks(): void
    {
        $this->createCustomerRequest = $this
            ->createMock(RequestBody::class);
        $this->setupResponseMocks();
    }

    private function setupResponseMocks(): void
    {
        $this->validResponse = $this->createResponseMock();
        $this->badRequestResponse = $this->createResponseMock();
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
        $this->operationPost = $this->createMock(Operation::class);
        $this->operationGet = $this->createMock(Operation::class);
    }

    private function setupFactoryReturnValues(): void
    {
        $this->createFactory->method('getRequest')
            ->willReturn($this->createCustomerRequest);
        $this->validationErrorFactory->method('getResponse')
            ->willReturn($this->validResponse);
        $this->badRequestResponseFactory->method('getResponse')
            ->willReturn($this->badRequestResponse);
        $this->internalErrorFactory->method('getResponse')
            ->willReturn($this->internalResponse);
        $this->forbiddenResponseFactory->method('getResponse')
            ->willReturn($this->forbiddenResponse);
        $this->unauthorizedResponseFactory->method('getResponse')
            ->willReturn($this->unauthorizedResponse);
    }

    private function setExpectations(): void
    {
        $this->setupPaths();
        $this->setupOperations();
        $this->setupResponses();
        $this->setupPathItemWithOperations();
    }

    private function setupPaths(): void
    {
        $this->openApi->method('getPaths')->willReturn($this->paths);
        $this->paths->expects($this->once())
            ->method('getPath')
            ->with('/api/customers')
            ->willReturn($this->pathItem);
    }

    private function setupOperations(): void
    {
        $this->pathItem->expects($this->once())
            ->method('getPost')
            ->willReturn($this->operationPost);
        $this->pathItem->expects($this->once())
            ->method('getGet')
            ->willReturn($this->operationGet);
        $this->operationPost->expects($this->once())
            ->method('getResponses')
            ->willReturn([]);
        $this->operationGet->expects($this->once())
            ->method('getResponses')
            ->willReturn([]);
    }

    private function setupResponses(): void
    {
        $postResponses = $this->getPostResponses();
        $getResponses = $this->getGetResponses();

        $this->operationPost->expects($this->once())
            ->method('withResponses')
            ->with($postResponses)
            ->willReturnSelf();
        $this->operationPost->expects($this->once())
            ->method('withRequestBody')
            ->with($this->createCustomerRequest)
            ->willReturnSelf();
        $this->operationGet->expects($this->once())
            ->method('withResponses')
            ->with($getResponses)
            ->willReturnSelf();
    }

    private function setupPathItemWithOperations(): void
    {
        $this->pathItem->expects($this->once())
            ->method('withPost')
            ->with($this->operationPost)
            ->willReturnSelf();
        $this->pathItem->expects($this->once())
            ->method('withGet')
            ->with($this->operationGet)
            ->willReturnSelf();
        $this->paths->expects($this->once())
            ->method('addPath')
            ->with('/api/customers', $this->pathItem);
    }

    /**
     * @return array<int, Response>
     */
    private function getPostResponses(): array
    {
        return [
            HttpResponse::HTTP_BAD_REQUEST => $this->badRequestResponse,
            HttpResponse::HTTP_UNAUTHORIZED => $this->unauthorizedResponse,
            HttpResponse::HTTP_FORBIDDEN => $this->forbiddenResponse,
            HttpResponse::HTTP_UNPROCESSABLE_ENTITY => $this->validResponse,
            HttpResponse::HTTP_INTERNAL_SERVER_ERROR => $this->internalResponse,
        ];
    }

    /**
     * @return array<int, Response>
     */
    private function getGetResponses(): array
    {
        return [
            HttpResponse::HTTP_BAD_REQUEST => $this->badRequestResponse,
            HttpResponse::HTTP_UNAUTHORIZED => $this->unauthorizedResponse,
            HttpResponse::HTTP_FORBIDDEN => $this->forbiddenResponse,
            HttpResponse::HTTP_INTERNAL_SERVER_ERROR => $this->internalResponse,
        ];
    }
}
