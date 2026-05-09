<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator\Guard;

use App\Shared\Application\Validator\Guard\PatchPayloadGuard;
use App\Tests\Unit\UnitTestCase;
use stdClass;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class PatchPayloadGuardTest extends UnitTestCase
{
    public function testAssertContainsAnyFieldAllowsNonNullSupportedField(): void
    {
        $this->expectNotToPerformAssertions();

        $payload = new stdClass();
        $payload->name = 'Customer';
        $payload->email = null;

        PatchPayloadGuard::assertContainsAnyField($payload, [
            'name',
            'email',
        ]);
    }

    public function testAssertContainsAnyFieldAllowsFalseBoolean(): void
    {
        $this->expectNotToPerformAssertions();

        $payload = new stdClass();
        $payload->confirmed = false;

        PatchPayloadGuard::assertContainsAnyField($payload, ['confirmed']);
    }

    public function testAssertContainsAnyFieldRejectsAllNullFields(): void
    {
        $payload = new stdClass();
        $payload->name = null;
        $payload->email = null;

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage(PatchPayloadGuard::EMPTY_PAYLOAD_MESSAGE);

        PatchPayloadGuard::assertContainsAnyField($payload, [
            'name',
            'email',
        ]);
    }

    public function testAssertContainsAnyFieldRejectsBlankStringFields(): void
    {
        $payload = new stdClass();
        $payload->name = '   ';

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage(PatchPayloadGuard::EMPTY_PAYLOAD_MESSAGE);

        PatchPayloadGuard::assertContainsAnyField($payload, ['name']);
    }

    public function testAssertContainsAnyFieldRejectsMissingFields(): void
    {
        $payload = new stdClass();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage(PatchPayloadGuard::EMPTY_PAYLOAD_MESSAGE);

        PatchPayloadGuard::assertContainsAnyField($payload, ['name']);
    }
}
