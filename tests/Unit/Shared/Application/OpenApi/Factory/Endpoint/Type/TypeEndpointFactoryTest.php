<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Endpoint\Type;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Endpoint\CustomerType\CustomerTypeEndpointFactory;
use App\Shared\Application\OpenApi\Factory\Request\CustomerType\CrCTyReq;
use App\Shared\Application\OpenApi\Factory\Response\BadRequestResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ForbiddenResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\InternalErrorFactory;
use App\Shared\Application\OpenApi\Factory\Response\UnauthorizedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\ValidationErrorFactory;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class TypeEndpointFactoryTest extends UnitTestCase
{
    private CrCTyReq $createFactory;
    private ValidationErrorFactory $validationErrorFactory;
    private BadRequestResponseFactory $badRequestResponseFactory;
    private InternalErrorFactory $internalErrorFactory;
    private ForbiddenResponseFactory $forbiddenResponseFactory;
    private UnauthorizedResponseFactory $unauthorizedResponseFactory;

    private RequestBody $createCustomerTypeRequest;
    private Response $validResponse;
    private Response $badRequestResponse;
    private Response $internalResponse;
    private Response $forbiddenResponse;
    private Response $unauthorizedResponse;

    private OpenApi $openApi;
    private Paths $paths;
    private PathItem $pathItem;
    private Operation $operationPost;
    private Operation $operationGet;

    /**
     * Properties to capture the final responses passed into the operations.
     *
     * @var array<int, Response>
     */
    private array $postResponses = [];

    /**
     * @var array<int, Response>
     */
    private array $responses = [];

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

        $expectedPostResponses = $this->getPostExpectedResponses();
        $expectedGetResponses = $this->getGetExpectedResponses();

        $this->assertEquals(
            $expectedPostResponses,
            $this->postResponses,
            'Post operation responses do not match the expected values.'
        );
        $this->assertEquals(
            $expectedGetResponses,
            $this->responses,
            'Get operation responses do not match the expected values.'
        );
    }

    private function setupFactoryMocks(): void
    {
        $this->createFactory = $this
            ->createMock(CrCTyReq::class);
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
        $this->createCustomerTypeRequest = $this
            ->createMock(RequestBody::class);
        $this->validResponse = $this
            ->getMockBuilder(Response::class)
            ->disableOriginalConstructor()->getMock();
        $this->badRequestResponse = $this
            ->getMockBuilder(Response::class)
            ->disableOriginalConstructor()->getMock();
        $this->internalResponse = $this
            ->getMockBuilder(Response::class)
            ->disableOriginalConstructor()->getMock();
        $this->forbiddenResponse = $this
            ->getMockBuilder(Response::class)
            ->disableOriginalConstructor()->getMock();
        $this->unauthorizedResponse = $this
            ->getMockBuilder(Response::class)
            ->disableOriginalConstructor()->getMock();
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
            ->willReturn($this->createCustomerTypeRequest);
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

    private function createFactory(): CustomerTypeEndpointFactory
    {
        return new CustomerTypeEndpointFactory(
            $this->createFactory,
            $this->validationErrorFactory,
            $this->badRequestResponseFactory,
            $this->internalErrorFactory,
            $this->forbiddenResponseFactory,
            $this->unauthorizedResponseFactory
        );
    }

    private function setExpectations(): void
    {
        $this->setupOpenApiAndPathsExpectations();
        $this->setupPathItemExpectations();
        $this->setupOperationResponsesExpectations();
        $this->setupOperationsWithResponses();
        $this->setupPathItemWithOperations();
    }

    private function setupOpenApiAndPathsExpectations(): void
    {
        $this->openApi->method('getPaths')->willReturn($this->paths);
        $this->paths->expects($this->once())
            ->method('getPath')
            ->with('/api/customer_types')
            ->willReturn($this->pathItem);
    }

    private function setupPathItemExpectations(): void
    {
        $this->pathItem->expects($this->once())
            ->method('getPost')
            ->willReturn($this->operationPost);
        $this->pathItem->expects($this->once())
            ->method('getGet')
            ->willReturn($this->operationGet);
    }

    private function setupOperationResponsesExpectations(): void
    {
        $this->operationPost->expects($this->once())
            ->method('getResponses')
            ->willReturn([]);
        $this->operationGet->expects($this->once())
            ->method('getResponses')
            ->willReturn([]);
    }

    private function setupOperationsWithResponses(): void
    {
        $postExpected = $this->getPostExpectedResponses();
        $getExpected = $this->getGetExpectedResponses();

        $this->operationPost->expects($this->once())
            ->method('withResponses')
            ->with($this->callback(function ($responses) use ($postExpected) {
                $this->postResponses = $responses;
                return $responses === $postExpected;
            }))
            ->willReturnSelf();
        $this->operationPost->expects($this->once())
            ->method('withRequestBody')
            ->with($this->createCustomerTypeRequest)
            ->willReturnSelf();

        $this->operationGet->expects($this->once())
            ->method('withResponses')
            ->with($this->callback(function ($responses) use ($getExpected) {
                $this->responses = $responses;
                return $responses === $getExpected;
            }))
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
            ->with('/api/customer_types', $this->pathItem);
    }

    /**
     * @return array<int, Response>
     */
    private function getPostExpectedResponses(): array
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
    private function getGetExpectedResponses(): array
    {
        return [
            HttpResponse::HTTP_BAD_REQUEST => $this->badRequestResponse,
            HttpResponse::HTTP_UNAUTHORIZED => $this->unauthorizedResponse,
            HttpResponse::HTTP_FORBIDDEN => $this->forbiddenResponse,
            HttpResponse::HTTP_INTERNAL_SERVER_ERROR => $this->internalResponse,
        ];
    }
}
