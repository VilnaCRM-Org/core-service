<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\DTO;

use App\Core\Customer\Domain\Entity\Customer;
use App\Shared\Application\DTO\CacheInvalidationRule;
use App\Tests\Unit\UnitTestCase;

final class CacheInvalidationRuleTest extends UnitTestCase
{
    public function testCreateKeepsRulePayload(): void
    {
        $rule = CacheInvalidationRule::create(
            'customer',
            'odm_change_set',
            Customer::class,
            CacheInvalidationRule::OPERATION_UPDATED,
            'repository_refresh'
        );

        self::assertSame('customer', $rule->context());
        self::assertSame('odm_change_set', $rule->source());
        self::assertSame(Customer::class, $rule->subject());
        self::assertSame(CacheInvalidationRule::OPERATION_UPDATED, $rule->operation());
        self::assertSame('repository_refresh', $rule->refreshSource());
    }
}
