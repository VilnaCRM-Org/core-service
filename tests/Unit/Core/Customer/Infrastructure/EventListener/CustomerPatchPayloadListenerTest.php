<?php

declare(strict_types=1);

namespace App\Tests\Unit\Core\Customer\Infrastructure\EventListener;

use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Infrastructure\EventListener\CustomerPatchPayloadListener;
use App\Shared\Application\Validator\Guard\PatchPayloadGuard;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class CustomerPatchPayloadListenerTest extends UnitTestCase
{
    private CustomerPatchPayloadListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new CustomerPatchPayloadListener(new PatchPayloadGuard());
    }

    public function testRejectsEmptyCustomerPatchPayload(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage(PatchPayloadGuard::EMPTY_PAYLOAD_MESSAGE);

        ($this->listener)($this->createPatchEvent(Customer::class, '{}'));
    }

    public function testRejectsEmptyCustomerPatchRequestContent(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage(PatchPayloadGuard::EMPTY_PAYLOAD_MESSAGE);

        ($this->listener)($this->createPatchEvent(Customer::class, ''));
    }

    public function testRejectsUnsupportedCustomerPatchPayload(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage(PatchPayloadGuard::EMPTY_PAYLOAD_MESSAGE);

        ($this->listener)($this->createPatchEvent(Customer::class, '{"unknown":"value"}'));
    }

    public function testAllowsBlankStatusPatchPayloadForValidation(): void
    {
        $this->expectNotToPerformAssertions();

        ($this->listener)($this->createPatchEvent(CustomerStatus::class, '{"value":"   "}'));
    }

    public function testAllowsCustomerPatchPayloadWithSupportedField(): void
    {
        $this->expectNotToPerformAssertions();

        ($this->listener)($this->createPatchEvent(Customer::class, '{"confirmed":false}'));
    }

    public function testAllowsCustomerTypePatchPayloadWithValue(): void
    {
        $this->expectNotToPerformAssertions();

        ($this->listener)($this->createPatchEvent(CustomerType::class, '{"value":"VIP"}'));
    }

    public function testIgnoresNonPatchRequests(): void
    {
        $this->expectNotToPerformAssertions();

        $request = Request::create('/api/customers/01H00000000000000000000000', Request::METHOD_GET);
        $request->attributes->set('_api_resource_class', Customer::class);

        ($this->listener)($this->createRequestEvent($request));
    }

    public function testIgnoresUnsupportedResources(): void
    {
        $this->expectNotToPerformAssertions();

        ($this->listener)($this->createPatchEvent(self::class, '{}'));
    }

    public function testIgnoresInvalidJsonPayload(): void
    {
        $this->expectNotToPerformAssertions();

        ($this->listener)($this->createPatchEvent(Customer::class, '{"email":'));
    }

    public function testIgnoresSubRequests(): void
    {
        $this->expectNotToPerformAssertions();

        $request = $this->createPatchRequest(Customer::class, '{}');
        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::SUB_REQUEST
        );

        ($this->listener)($event);
    }

    private function createPatchEvent(string $resourceClass, string $content): RequestEvent
    {
        return $this->createRequestEvent($this->createPatchRequest($resourceClass, $content));
    }

    private function createPatchRequest(string $resourceClass, string $content): Request
    {
        $request = Request::create(
            '/api/customers/01H00000000000000000000000',
            Request::METHOD_PATCH,
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/merge-patch+json'],
            $content
        );
        $request->attributes->set('_api_resource_class', $resourceClass);

        return $request;
    }

    private function createRequestEvent(Request $request): RequestEvent
    {
        return new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );
    }
}
