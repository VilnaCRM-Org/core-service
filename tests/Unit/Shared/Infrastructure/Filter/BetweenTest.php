<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Filter;

use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\Filter\Between;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage\MatchStage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class BetweenTest extends TestCase
{
    private Between $operator;
    private Builder|MockObject $builder;
    private MatchStage|MockObject $matchStage;

    protected function setUp(): void
    {
        $this->operator = new Between();
        $this->builder = $this->createMock(Builder::class);
        $this->matchStage = $this->createMock(MatchStage::class);
    }

    public function testApplyWithValidRange(): void
    {
        $field = 'ulid';
        $min = new Ulid('01JKX8XGHVDZ46MWYMZT94YER4');
        $max = new Ulid('01JKX8XGHVDZ46MWYMZT94YER5');
        $filterValue = [$min, $max];

        $this->builder->expects($this->once())
            ->method('match')
            ->willReturn($this->matchStage);

        $this->matchStage->expects($this->once())
            ->method('field')
            ->with($field)
            ->willReturn($this->matchStage);

        $this->matchStage->expects($this->once())
            ->method('gte')
            ->with($min)
            ->willReturn($this->matchStage);

        $this->matchStage->expects($this->once())
            ->method('lte')
            ->with($max)
            ->willReturn($this->matchStage);

        $this->operator->apply($this->builder, $field, $filterValue);
    }

    public function testApplyWithNonArrayValue(): void
    {
        $field = 'ulid';
        $filterValue = new Ulid('01JKX8XGHVDZ46MWYMZT94YER4');

        $this->builder->expects($this->never())
            ->method('match');

        $this->operator->apply($this->builder, $field, $filterValue);
    }
}
