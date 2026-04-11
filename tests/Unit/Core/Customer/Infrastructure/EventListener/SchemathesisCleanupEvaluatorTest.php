<?php

declare(strict_types=1);

namespace App\Tests\Unit\Core\Customer\Infrastructure\EventListener;

use App\Core\Customer\Infrastructure\EventListener\SchemathesisCleanupEvaluator;
use App\Tests\Unit\UnitTestCase;
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
        $request = Request::create(
            '/api/customers',
            Request::METHOD_POST,
            server: ['HTTP_X_SCHEMATHESIS_TEST' => 'cleanup-customers']
        );
        $response = new Response('', Response::HTTP_CREATED);

        $result = $this->evaluator->shouldCleanup($request, $response);

        $this->assertTrue($result);
    }

    public function testShouldNotCleanupWithoutHeader(): void
    {
        $request = Request::create('/api/customers', Request::METHOD_POST);
        $response = new Response('', Response::HTTP_CREATED);

        $result = $this->evaluator->shouldCleanup($request, $response);

        $this->assertFalse($result);
    }

    public function testShouldNotCleanupWithWrongPath(): void
    {
        $request = Request::create(
            '/api/wrong-path',
            Request::METHOD_POST,
            server: ['HTTP_X_SCHEMATHESIS_TEST' => 'cleanup-customers']
        );
        $response = new Response('', Response::HTTP_CREATED);

        $result = $this->evaluator->shouldCleanup($request, $response);

        $this->assertFalse($result);
    }

    public function testShouldNotCleanupWithUnsuccessfulResponse(): void
    {
        $request = Request::create(
            '/api/customers',
            Request::METHOD_POST,
            server: ['HTTP_X_SCHEMATHESIS_TEST' => 'cleanup-customers']
        );
        $response = new Response('', Response::HTTP_BAD_REQUEST);

        $result = $this->evaluator->shouldCleanup($request, $response);

        $this->assertFalse($result);
    }

    public function testShouldNotCleanupForNonPostRequest(): void
    {
        $request = Request::create(
            '/api/customers',
            Request::METHOD_PATCH,
            server: ['HTTP_X_SCHEMATHESIS_TEST' => 'cleanup-customers']
        );
        $response = new Response('', Response::HTTP_OK);

        $result = $this->evaluator->shouldCleanup($request, $response);

        $this->assertFalse($result);
    }
}
