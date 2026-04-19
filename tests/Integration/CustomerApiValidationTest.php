<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Core\Customer\Domain\Factory\CustomerFactoryInterface;
use App\Core\Customer\Domain\Factory\StatusFactoryInterface;
use App\Core\Customer\Domain\Factory\TypeFactoryInterface;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Core\Customer\Domain\Repository\StatusRepositoryInterface;
use App\Core\Customer\Domain\Repository\TypeRepositoryInterface;
use App\Shared\Infrastructure\Factory\UlidFactory;

final class CustomerApiValidationTest extends BaseApiCase
{
    public function testCreateCustomerWithoutInitials(): void
    {
        $payload = $this->getCustomer();
        unset($payload['initials']);

        $client = self::createClient();
        $client->request('POST', '/api/customers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'initials: This value should not be blank',
            $error['detail']
        );
    }

    public function testCreateCustomerWithTooLongInitials(): void
    {
        $payload = $this->getCustomer();
        $payload['initials'] = str_repeat('X', 256);

        $client = self::createClient();
        $client->request('POST', '/api/customers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'initials: This value is too long',
            $error['detail']
        );
    }

    public function testCreateCustomerWithInvalidEmail(): void
    {
        $payload = $this->getCustomer();
        $payload['email'] = 'not-an-email';

        $client = self::createClient();
        $client->request('POST', '/api/customers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'email: This value is not a valid email address.',
            $error['detail']
        );
    }

    public function testCreateCustomerWithTooLongEmail(): void
    {
        $payload = $this->getCustomer();
        $payload['email'] = str_repeat('a', 256) . '@example.com';

        $client = self::createClient();
        $client->request('POST', '/api/customers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'email: This value is too long',
            $error['detail']
        );
    }

    public function testCreateCustomerWithDuplicateEmail(): void
    {
        $payload = $this->getCustomer();
        $this->createEntity('/api/customers', $payload);

        $client = self::createClient();
        $client->request('POST', '/api/customers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('email:', $error['detail']);
    }

    public function testCreateCustomerWithoutPhone(): void
    {
        $payload = $this->getCustomer();
        unset($payload['phone']);

        $client = self::createClient();
        $client->request('POST', '/api/customers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'phone: This value should not be blank',
            $error['detail']
        );
    }

    public function testCreateCustomerWithTooLongPhone(): void
    {
        $payload = $this->getCustomer();
        $payload['phone'] = str_repeat('9', 256);

        $client = self::createClient();
        $client->request('POST', '/api/customers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'phone: This value is too long',
            $error['detail']
        );
    }

    public function testCreateCustomerWithoutLeadSource(): void
    {
        $payload = $this->getCustomer();
        unset($payload['leadSource']);

        $client = self::createClient();
        $client->request('POST', '/api/customers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'leadSource: This value should not be blank',
            $error['detail']
        );
    }

    public function testCreateCustomerWithTooLongLeadSource(): void
    {
        $payload = $this->getCustomer();
        $payload['leadSource'] = str_repeat('L', 256);

        $client = self::createClient();
        $client->request('POST', '/api/customers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'leadSource: This value is too long',
            $error['detail']
        );
    }

    public function testCreateCustomerWithoutType(): void
    {
        $payload = $this->getCustomer();
        unset($payload['type']);

        $client = self::createClient();
        $client->request('POST', '/api/customers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'type: This value should not be blank.',
            $error['detail']
        );
    }

    public function testCreateCustomerWithoutStatus(): void
    {
        $payload = $this->getCustomer();
        unset($payload['status']);

        $client = self::createClient();
        $client->request('POST', '/api/customers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'status: This value should not be blank',
            $error['detail']
        );
    }

    public function testCreateCustomerWithNonBooleanConfirmed(): void
    {
        $payload = $this->getCustomer();
        $payload['confirmed'] = 'yes';

        $client = self::createClient();
        $client->request('POST', '/api/customers', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(400);
        $this->assertStringContainsString(
            'The input data is misformatted.',
            $error['detail']
        );
    }

    public function testPatchCustomerWithTooLongInitials(): void
    {
        $iri = $this->createEntity('/api/customers', $this->getCustomer());
        $patch = ['initials' => str_repeat('X', 256)];

        $client = self::createClient();
        $client->request('PATCH', $iri, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'body' => json_encode($patch),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'initials: This value is too long',
            $error['detail']
        );
    }

    public function testPatchRepositorySeededCustomerWithScalarFieldsOnly(): void
    {
        $customerId = (string) $this->faker->ulid();
        $typeId = (string) $this->faker->ulid();
        $statusId = (string) $this->faker->ulid();
        $ulidFactory = $this->container->get(UlidFactory::class);
        $typeFactory = $this->container->get(TypeFactoryInterface::class);
        $statusFactory = $this->container->get(StatusFactoryInterface::class);
        $customerFactory = $this->container->get(CustomerFactoryInterface::class);
        $typeRepository = $this->container->get(TypeRepositoryInterface::class);
        $statusRepository = $this->container->get(StatusRepositoryInterface::class);
        $customerRepository = $this->container->get(CustomerRepositoryInterface::class);

        $type = $typeFactory->create('seeded-type', $ulidFactory->create($typeId));
        $status = $statusFactory->create('seeded-status', $ulidFactory->create($statusId));

        $typeRepository->save($type);
        $statusRepository->save($status);

        $customer = $customerFactory->create(
            'Seeded Customer',
            $this->faker->unique()->safeEmail(),
            '+1234567890',
            'Seeded',
            $type,
            $status,
            true,
            $ulidFactory->create($customerId)
        );
        $customerRepository->save($customer);

        $client = self::createClient();
        $client->request('PATCH', "/api/customers/{$customerId}", [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'body' => json_encode([
                'phone' => '0987654321',
                'leadSource' => 'Facebook',
            ]),
        ]);

        $this->assertResponseIsSuccessful();
        $data = (self::createClient()->request('GET', "/api/customers/{$customerId}"))->toArray();
        $this->assertSame('0987654321', $data['phone']);
        $this->assertSame('Facebook', $data['leadSource']);
        $this->assertSame("/api/customer_types/{$typeId}", $data['type']['@id']);
        $this->assertSame("/api/customer_statuses/{$statusId}", $data['status']['@id']);
    }

    public function testPatchCustomerWithInvalidEmail(): void
    {
        $iri = $this->createEntity('/api/customers', $this->getCustomer());
        $patch = ['email' => 'bad@'];

        $client = self::createClient();
        $client->request('PATCH', $iri, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'body' => json_encode($patch),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'email: This value is not a valid email address.',
            $error['detail']
        );
    }

    public function testPatchCustomerWithTooLongEmail(): void
    {
        $iri = $this->createEntity('/api/customers', $this->getCustomer());
        $patch = ['email' => str_repeat('a', 256) . '@x.com'];

        $client = self::createClient();
        $client->request('PATCH', $iri, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'body' => json_encode($patch),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'email: This value is too long',
            $error['detail']
        );
    }

    public function testPatchCustomerWithDuplicateEmail(): void
    {
        $existing = $this->getCustomer();
        $this->createEntity('/api/customers', $existing);

        $iri = $this->createEntity('/api/customers', $this->getCustomer());
        $patch = ['email' => $existing['email']];

        $client = self::createClient();
        $client->request('PATCH', $iri, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'body' => json_encode($patch),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('email:', $error['detail']);
    }

    public function testPatchCustomerWithTooLongPhone(): void
    {
        $iri = $this->createEntity('/api/customers', $this->getCustomer());
        $patch = ['phone' => str_repeat('9', 256)];

        $client = self::createClient();
        $client->request('PATCH', $iri, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'body' => json_encode($patch),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'phone: This value is too long',
            $error['detail']
        );
    }

    public function testPatchCustomerWithTooLongLeadSource(): void
    {
        $iri = $this->createEntity('/api/customers', $this->getCustomer());
        $patch = ['leadSource' => str_repeat('L', 256)];

        $client = self::createClient();
        $client->request('PATCH', $iri, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'body' => json_encode($patch),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'leadSource: This value is too long',
            $error['detail']
        );
    }

    public function testPatchCustomerWithNonBooleanConfirmed(): void
    {
        $iri = $this->createEntity('/api/customers', $this->getCustomer());
        $patch = ['confirmed' => 'maybe'];

        $client = self::createClient();
        $client->request('PATCH', $iri, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'body' => json_encode($patch),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(400);
        $this->assertStringContainsString(
            'The input data is misformatted.',
            $error['detail']
        );
    }

    public function testReplaceCustomerWithoutInitials(): void
    {
        $payload = $this->getCustomer();
        $iri = $this->createEntity('/api/customers', $payload);
        unset($payload['initials']);

        $client = self::createClient();
        $client->request('PUT', $iri, [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'initials: This value should not be blank',
            $error['detail']
        );
    }

    public function testReplaceCustomerWithTooLongInitials(): void
    {
        $payload = $this->getCustomer();
        $payload['initials'] = str_repeat('X', 256);
        $iri = $this->createEntity('/api/customers', $this->getCustomer());

        $client = self::createClient();
        $client->request('PUT', $iri, [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'initials: This value is too long',
            $error['detail']
        );
    }

    public function testReplaceCustomerWithoutEmail(): void
    {
        $payload = $this->getCustomer();
        unset($payload['email']);
        $iri = $this->createEntity('/api/customers', $this->getCustomer());

        $client = self::createClient();
        $client->request('PUT', $iri, [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'email: This value should not be blank',
            $error['detail']
        );
    }

    public function testReplaceCustomerWithInvalidEmailFormat(): void
    {
        $payload = $this->getCustomer();
        $payload['email'] = 'wrong';
        $iri = $this->createEntity('/api/customers', $this->getCustomer());

        $client = self::createClient();
        $client->request('PUT', $iri, [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'email: This value is not a valid email address.',
            $error['detail']
        );
    }

    public function testReplaceCustomerWithTooLongEmail(): void
    {
        $payload = $this->getCustomer();
        $payload['email'] = str_repeat('a', 256) . '@x.com';
        $iri = $this->createEntity('/api/customers', $this->getCustomer());

        $client = self::createClient();
        $client->request('PUT', $iri, [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'email: This value is too long',
            $error['detail']
        );
    }

    public function testReplaceCustomerWithoutPhone(): void
    {
        $payload = $this->getCustomer();
        unset($payload['phone']);
        $iri = $this->createEntity('/api/customers', $this->getCustomer());

        $client = self::createClient();
        $client->request('PUT', $iri, [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'phone: This value should not be blank',
            $error['detail']
        );
    }

    public function testReplaceCustomerWithTooLongPhone(): void
    {
        $payload = $this->getCustomer();
        $payload['phone'] = str_repeat('9', 256);
        $iri = $this->createEntity('/api/customers', $this->getCustomer());

        $client = self::createClient();
        $client->request('PUT', $iri, [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'phone: This value is too long',
            $error['detail']
        );
    }

    public function testReplaceCustomerWithoutLeadSource(): void
    {
        $payload = $this->getCustomer();
        unset($payload['leadSource']);
        $iri = $this->createEntity('/api/customers', $this->getCustomer());

        $client = self::createClient();
        $client->request('PUT', $iri, [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'leadSource: This value should not be blank',
            $error['detail']
        );
    }

    public function testReplaceCustomerWithTooLongLeadSource(): void
    {
        $payload = $this->getCustomer();
        $payload['leadSource'] = str_repeat('L', 256);
        $iri = $this->createEntity('/api/customers', $this->getCustomer());

        $client = self::createClient();
        $client->request('PUT', $iri, [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'leadSource: This value is too long',
            $error['detail']
        );
    }

    public function testReplaceCustomerWithoutType(): void
    {
        $payload = $this->getCustomer();
        unset($payload['type']);
        $iri = $this->createEntity('/api/customers', $this->getCustomer());

        $client = self::createClient();
        $client->request('PUT', $iri, [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'type: This value should not be blank.',
            $error['detail']
        );
    }

    public function testReplaceCustomerWithoutStatus(): void
    {
        $payload = $this->getCustomer();
        unset($payload['status']);
        $iri = $this->createEntity('/api/customers', $this->getCustomer());

        $client = self::createClient();
        $client->request('PUT', $iri, [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'status: This value should not be blank',
            $error['detail']
        );
    }

    public function testReplaceCustomerWithNonBooleanConfirmed(): void
    {
        $payload = $this->getCustomer();
        $payload['confirmed'] = 'nope';
        $iri = $this->createEntity('/api/customers', $this->getCustomer());

        $client = self::createClient();
        $client->request('PUT', $iri, [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(400);
        $this->assertStringContainsString(
            'The input data is misformatted.',
            $error['detail']
        );
    }

    public function testReplaceCustomerWithNullConfirmed(): void
    {
        $payload = $this->getCustomer();
        $payload['confirmed'] = null;
        $iri = $this->createEntity('/api/customers', $this->getCustomer());

        $client = self::createClient();
        $client->request('PUT', $iri, [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($payload),
        ]);

        $error = $client->getResponse()->toArray(false);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            'confirmed: This value should not be null.',
            $error['detail']
        );
    }

    /**
     * @return array<string, string|bool>
     */
    private function getCustomer(string $initials = 'Test Customer'): array
    {
        return [
            'email' => $this->faker->unique()->email(),
            'phone' => $this->faker->phoneNumber(),
            'initials' => $initials,
            'leadSource' => $this->faker->word(),
            'type' => $this->createCustomerType(),
            'status' => $this->createCustomerStatus(),
            'confirmed' => true,
        ];
    }

    private function createCustomerStatus(): string
    {
        return $this->createEntity(
            '/api/customer_statuses',
            ['value' => $this->faker->word()],
            'CustomerStatus'
        );
    }

    private function createCustomerType(): string
    {
        return $this->createEntity(
            '/api/customer_types',
            ['value' => $this->faker->word()],
            'CustomerType'
        );
    }
}
