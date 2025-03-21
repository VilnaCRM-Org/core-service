<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Filter;

use App\Shared\Infrastructure\Filter\UlidFilterProcessor;
use App\Tests\Unit\UnitTestCase;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage\MatchStage;
use PHPUnit\Framework\MockObject\MockObject;

final class UlidFilterProcessorTest extends UnitTestCase
{
    private UlidFilterProcessor $processor;
    private Builder|MockObject $builder;
    private MatchStage|MockObject $matchStage;

    protected function setUp(): void
    {
        $this->processor = new UlidFilterProcessor();
        $this->builder = $this->createMock(Builder::class);
        $this->matchStage = $this->createMock(MatchStage::class);
    }

    public function testProcessWithNonUlidProperty(): void
    {
        $this->processor->process(
            'email',
            'lt',
            'test@example.com',
            $this->builder
        );
        $this->builder->expects($this->never())->method('match');
    }

    public function testProcessWithNonStringValue(): void
    {
        $this->processor->process('ulid', 'lt', 123, $this->builder);
        $this->builder->expects($this->never())->method('match');
    }

    public function testProcessWithValidUlid(): void
    {
        $ulid = '01JKX8XGHVDZ46MWYMZT94YER4';
        $this->builder->expects($this->once())
            ->method('match')
            ->willReturn($this->matchStage);

        $this->processor->process('ulid', 'lt', $ulid, $this->builder);
    }

    public function testProcessWithUlidRange(): void
    {
        $range = '01JKX8XGHVDZ46MWYMZT94YER4..01JKX8XGHVDZ46MWYMZT94YER5';
        $this->builder->expects($this->once())
            ->method('match')
            ->willReturn($this->matchStage);

        $this->processor->process('ulid', 'between', $range, $this->builder);
    }

    public function testProcessWithDifferentOperators(): void
    {
        $ulid = '01JKX8XGHVDZ46MWYMZT94YER4';
        $this->builder->expects($this->exactly(4))
            ->method('match')
            ->willReturn($this->matchStage);

        $this->processor->process('ulid', 'lt', $ulid, $this->builder);
        $this->processor->process('ulid', 'lte', $ulid, $this->builder);
        $this->processor->process('ulid', 'gt', $ulid, $this->builder);
        $this->processor->process('ulid', 'gte', $ulid, $this->builder);
    }
}
