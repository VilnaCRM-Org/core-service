<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Filter;

use App\Shared\Infrastructure\Filter\Between;
use App\Tests\Unit\UnitTestCase;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage\MatchStage;

final class BetweenTest extends UnitTestCase
{
    public function testApplyWithValidArray(): void
    {
        $between = new Between();
        $builder = $this->createMock(Builder::class);
        $matchStage = $this->createMock(MatchStage::class);

        $builder->expects($this->once())
            ->method('match')
            ->willReturn($matchStage);

        $matchStage->expects($this->once())
            ->method('field')
            ->with('testField')
            ->willReturn($matchStage);

        $matchStage->expects($this->once())
            ->method('gte')
            ->with(10)
            ->willReturn($matchStage);

        $matchStage->expects($this->once())
            ->method('lte')
            ->with(20)
            ->willReturn($matchStage);

        $between->apply($builder, 'testField', [10, 20]);
    }

    public function testApplyIgnoresNonArray(): void
    {
        $between = new Between();
        $builder = $this->createMock(Builder::class);

        $builder->expects($this->never())->method('match');

        $between->apply($builder, 'testField', 'not-an-array');
    }

    public function testApplyIgnoresWrongArraySize(): void
    {
        $between = new Between();
        $builder = $this->createMock(Builder::class);

        $builder->expects($this->never())->method('match');

        $between->apply($builder, 'testField', [1, 2, 3]);
    }

    public function testApplyIgnoresEmptyArray(): void
    {
        $between = new Between();
        $builder = $this->createMock(Builder::class);

        $builder->expects($this->never())->method('match');

        $between->apply($builder, 'testField', []);
    }
}
