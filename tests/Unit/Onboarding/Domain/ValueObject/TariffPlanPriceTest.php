<?php

declare(strict_types=1);

namespace App\Tests\Unit\Onboarding\Domain\ValueObject;

use App\Core\Onboarding\Domain\ValueObject\TariffPlanPrice;
use App\Tests\Unit\UnitTestCase;
use InvalidArgumentException;

final class TariffPlanPriceTest extends UnitTestCase
{
    public function testCanBeCreatedWithZeroPrice(): void
    {
        $price = new TariffPlanPrice(0, ' USD ', ' monthly ');

        self::assertSame(0, $price->getCents());
        self::assertSame('USD', $price->getCurrency());
        self::assertSame('monthly', $price->getPeriod());
    }

    public function testRejectsNegativeCents(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tariff plan price cents must be zero or greater.');

        new TariffPlanPrice(-1, 'USD', 'monthly');
    }

    public function testRejectsBlankCurrency(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tariff plan price currency must not be empty.');

        new TariffPlanPrice(100, ' ', 'monthly');
    }

    public function testRejectsBlankPeriod(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tariff plan price period must not be empty.');

        new TariffPlanPrice(100, 'USD', ' ');
    }
}
