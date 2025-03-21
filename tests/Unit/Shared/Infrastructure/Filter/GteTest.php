<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Filter;

use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\Filter\Gte;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage\MatchStage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GteTest extends TestCase
{
    private Gte $operator;
    private Builder|MockObject $builder;
    private MatchStage|MockObject $matchStage;

    protected function setUp(): void
    {
        $this->operator = new Gte();
        $this->builder = $this->createMock(Builder::class);
        $this->matchStage = $this->createMock(MatchStage::class);
    }

    public function testApply(): void
    {
        $field = 'ulid';
        $filterValue = new Ulid('01JKX8XGHVDZ46MWYMZT94YER4');

        $this->builder->expects($this->once())
            ->method('match')
            ->willReturn($this->matchStage);

        $this->matchStage->expects($this->once())
            ->method('field')
            ->with($field)
            ->willReturn($this->matchStage);

        $this->matchStage->expects($this->once())
            ->method('gte')
            ->with($filterValue)
            ->willReturn($this->matchStage);

        $this->operator->apply($this->builder, $field, $filterValue);
    }
}
