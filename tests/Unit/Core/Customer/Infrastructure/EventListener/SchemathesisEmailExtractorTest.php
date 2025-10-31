<?php

declare(strict_types=1);

namespace App\Tests\Unit\Core\Customer\Infrastructure\EventListener;

use App\Core\Customer\Infrastructure\EventListener\SchemathesisCleanupEvaluator;
use App\Core\Customer\Infrastructure\EventListener\SchemathesisEmailExtractor;
use App\Core\Customer\Infrastructure\EventListener\SchemathesisPayloadDecoder;
use App\Core\Customer\Infrastructure\EventListener\SchemathesisSingleCustomerEmailExtractor;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

final class SchemathesisEmailExtractorTest extends UnitTestCase
{
    private SchemathesisCleanupEvaluator $evaluator;
    private SchemathesisPayloadDecoder $payloadDecoder;
    private SchemathesisSingleCustomerEmailExtractor $singleCustomerExtractor;
    private SchemathesisEmailExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->evaluator = $this->createMock(SchemathesisCleanupEvaluator::class);
        $this->payloadDecoder = $this->createMock(SchemathesisPayloadDecoder::class);
        $this->singleCustomerExtractor = $this->createMock(SchemathesisSingleCustomerEmailExtractor::class);

        $this->extractor = new SchemathesisEmailExtractor(
            $this->evaluator,
            $this->payloadDecoder,
            $this->singleCustomerExtractor
        );
    }

    public function testExtractWithValidPayload(): void
    {
        $request = $this->createMock(Request::class);
        $payload = ['email' => 'test@example.com'];

        $this->payloadDecoder
            ->expects($this->once())
            ->method('decode')
            ->with($request)
            ->willReturn($payload);

        $this->singleCustomerExtractor
            ->expects($this->once())
            ->method('extract')
            ->with($payload)
            ->willReturn(['test@example.com']);

        $result = $this->extractor->extract($request);

        $this->assertEquals(['test@example.com'], $result);
    }

    public function testExtractWithEmptyPayload(): void
    {
        $request = $this->createMock(Request::class);

        $this->payloadDecoder
            ->expects($this->once())
            ->method('decode')
            ->with($request)
            ->willReturn([]);

        $this->singleCustomerExtractor
            ->expects($this->never())
            ->method('extract');

        $result = $this->extractor->extract($request);

        $this->assertEquals([], $result);
    }
}
