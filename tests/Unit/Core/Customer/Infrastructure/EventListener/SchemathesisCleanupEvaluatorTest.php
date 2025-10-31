<?php

declare(strict_types=1);

namespace App\Tests\Unit\Core\Customer\Infrastructure\EventListener;

use App\Core\Customer\Infrastructure\EventListener\SchemathesisCleanupEvaluator;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class SchemathesisCleanupEvaluatorTest extends UnitTestCase
{
    private SchemathesisCleanupEvaluator $evaluator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->evaluator = new SchemathesisCleanupEvaluator();
    }

    public function testShouldCleanupWithValidConditions(): void
    {
        $request = $this->createMock(Request::class);
        $response = new Response('', Response::HTTP_CREATED);
        $headers = $this->createMock(HeaderBag::class);

        $request->headers = $headers;
        $headers->method('get')->willReturn('cleanup-customers');
        $request->method('getPathInfo')->willReturn('/api/customers');

        $result = $this->evaluator->shouldCleanup($request, $response);

        $this->assertTrue($result);
    }

    public function testShouldNotCleanupWithoutHeader(): void
    {
        $request = $this->createMock(Request::class);
        $response = new Response('', Response::HTTP_CREATED);
        $headers = $this->createMock(HeaderBag::class);

        $request->headers = $headers;
        $headers->method('get')->willReturn(null);
        $request->method('getPathInfo')->willReturn('/api/customers');

        $result = $this->evaluator->shouldCleanup($request, $response);

        $this->assertFalse($result);
    }

    public function testShouldNotCleanupWithWrongPath(): void
    {
        $request = $this->createMock(Request::class);
        $response = new Response('', Response::HTTP_CREATED);
        $headers = $this->createMock(HeaderBag::class);

        $request->headers = $headers;
        $headers->method('get')->willReturn('cleanup-customers');
        $request->method('getPathInfo')->willReturn('/api/wrong-path');

        $result = $this->evaluator->shouldCleanup($request, $response);

        $this->assertFalse($result);
    }

    public function testShouldNotCleanupWithUnsuccessfulResponse(): void
    {
        $request = $this->createMock(Request::class);
        $response = new Response('', Response::HTTP_BAD_REQUEST);
        $headers = $this->createMock(HeaderBag::class);

        $request->headers = $headers;
        $headers->method('get')->willReturn('cleanup-customers');
        $request->method('getPathInfo')->willReturn('/api/customers');

        $result = $this->evaluator->shouldCleanup($request, $response);

        $this->assertFalse($result);
    }

    public function testIsSingleCustomerPathWithCorrectPath(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getPathInfo')->willReturn('/api/customers');

        $result = $this->evaluator->isSingleCustomerPath($request);

        $this->assertTrue($result);
    }

    public function testIsSingleCustomerPathWithWrongPath(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getPathInfo')->willReturn('/api/other');

        $result = $this->evaluator->isSingleCustomerPath($request);

        $this->assertFalse($result);
    }
}
