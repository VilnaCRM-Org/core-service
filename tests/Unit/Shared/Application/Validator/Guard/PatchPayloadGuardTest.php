<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator\Guard;

use App\Shared\Application\Validator\Guard\PatchPayloadGuard;
use App\Tests\Unit\UnitTestCase;
use stdClass;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class PatchPayloadGuardTest extends UnitTestCase
{
    private PatchPayloadGuard $guard;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guard = new PatchPayloadGuard();
    }

    public function testAssertContainsAnyFieldAllowsNonNullSupportedField(): void
    {
        $this->expectNotToPerformAssertions();

        $payload = new stdClass();
        $payload->name = 'Customer';
        $payload->email = null;

        $this->guard->assertContainsAnyField($payload, [
            'name',
            'email',
        ]);
    }

    public function testAssertContainsAnyFieldAllowsFalseBoolean(): void
    {
        $this->expectNotToPerformAssertions();

        $payload = new stdClass();
        $payload->confirmed = false;

        $this->guard->assertContainsAnyField($payload, ['confirmed']);
    }

    public function testAssertContainsAnyFieldAllowsArrayPayload(): void
    {
        $this->expectNotToPerformAssertions();

        $this->guard->assertContainsAnyField(
            ['confirmed' => false],
            ['confirmed']
        );
    }

    public function testAssertContainsAnyFieldAllowsArrayPayloadWhenSupportedFieldIsAfterIgnoredField(): void
    {
        $this->expectNotToPerformAssertions();

        $this->guard->assertContainsAnyField(
            [
                'ignored' => 'value',
                'confirmed' => false,
            ],
            ['confirmed']
        );
    }

    public function testAssertContainsAnyFieldAllowsDeclaredObjectProperty(): void
    {
        $this->expectNotToPerformAssertions();

        $payload = new class() {
            private string $name = 'Customer';
        };

        $this->guard->assertContainsAnyField($payload, ['name']);
    }

    public function testAssertContainsAnyFieldAllowsObjectPayloadWhenSupportedFieldIsAfterIgnoredField(): void
    {
        $this->expectNotToPerformAssertions();

        $payload = new stdClass();
        $payload->ignored = 'value';
        $payload->name = 'Customer';

        $this->guard->assertContainsAnyField($payload, ['name']);
    }

    public function testAssertContainsAnyFieldAllowsSupportedFieldAfterMissingField(): void
    {
        $this->expectNotToPerformAssertions();

        $payload = new stdClass();
        $payload->email = 'customer@example.com';

        $this->guard->assertContainsAnyField($payload, [
            'name',
            'email',
        ]);
    }

    public function testAssertContainsAnyFieldRejectsMissingFields(): void
    {
        $payload = new stdClass();

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage(PatchPayloadGuard::EMPTY_PAYLOAD_MESSAGE);

        $this->guard->assertContainsAnyField($payload, ['name']);
    }

    public function testAssertContainsAnyFieldRejectsNullSupportedFields(): void
    {
        $payload = new stdClass();
        $payload->name = null;
        $payload->email = null;

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage(PatchPayloadGuard::EMPTY_PAYLOAD_MESSAGE);

        $this->guard->assertContainsAnyField($payload, [
            'name',
            'email',
        ]);
    }

    public function testAssertContainsAnyFieldRejectsBlankStringField(): void
    {
        $payload = new stdClass();
        $payload->name = '   ';

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage(PatchPayloadGuard::EMPTY_PAYLOAD_MESSAGE);

        $this->guard->assertContainsAnyField($payload, ['name']);
    }

    public function testAssertContainsAnyFieldRejectsUninitializedObjectProperty(): void
    {
        $payload = new class() {
            private string $name;
        };

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage(PatchPayloadGuard::EMPTY_PAYLOAD_MESSAGE);

        $this->guard->assertContainsAnyField($payload, ['name']);
    }

    public function testAssertContainsAnyFieldAllowsConfiguredBlankStringField(): void
    {
        $this->expectNotToPerformAssertions();

        $payload = new stdClass();
        $payload->name = '   ';

        $this->guard->assertContainsAnyField(
            $payload,
            ['name'],
            ['name']
        );
    }

    public function testAssertContainsAnyFieldRejectsArrayPayloadWithBlankField(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage(PatchPayloadGuard::EMPTY_PAYLOAD_MESSAGE);

        $this->guard->assertContainsAnyField(
            ['name' => '   '],
            ['name']
        );
    }
}
