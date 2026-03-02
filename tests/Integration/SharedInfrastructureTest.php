<?php

declare(strict_types=1);

namespace App\Tests\Integration;

/**
 * Integration tests for shared infrastructure components.
 *
 * Tests infrastructure classes that support the application but might not be
 * fully covered by domain-specific tests.
 */
final class SharedInfrastructureTest extends BaseTest
{
    public function testUlidFilterGreaterThan(): void
    {
        $customer1Iri = $this->createEntity('/api/customers', $this->getCustomer('Range Test 1'));
        $ulid1 = basename($customer1Iri);

        $client = self::createClient();
        $response = $client->request('GET', '/api/customers', [
            'query' => ['ulid[gt]' => $ulid1],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHasHydraOrType($response->toArray());
    }

    public function testUlidFilterLessThan(): void
    {
        $customer3Iri = $this->createEntity('/api/customers', $this->getCustomer('Range Test 3'));
        $ulid3 = basename($customer3Iri);

        $client = self::createClient();
        $response = $client->request('GET', '/api/customers', [
            'query' => ['ulid[lt]' => $ulid3],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHasHydraOrType($response->toArray());
    }

    public function testUlidFilterBetween(): void
    {
        $customer1Iri = $this->createEntity('/api/customers', $this->getCustomer('Range Test 1'));
        $customer3Iri = $this->createEntity('/api/customers', $this->getCustomer('Range Test 3'));

        $ulid1 = basename($customer1Iri);
        $ulid3 = basename($customer3Iri);

        $client = self::createClient();
        $response = $client->request('GET', '/api/customers', [
            'query' => ['ulid[between]' => $ulid1 . '..' . $ulid3],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHasHydraOrType($response->toArray());
    }

    public function testUlidTransformerWithInvalidInput(): void
    {
        // Test API with invalid ULID format to trigger transformer error handling
        $client = self::createClient();

        // Try to get a customer with invalid ULID format
        $client->request('GET', '/api/customers/invalid-ulid-format');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testDoctrineUlidTypeConversion(): void
    {
        $customerIri = $this->createEntity('/api/customers', $this->getCustomer('ULID Type Test'));
        $data = $this->fetchResource($customerIri);
        $this->assertUlidCustomerResponse($data, $customerIri);
    }

    public function testFilterOperatorGte(): void
    {
        $this->createEntity('/api/customers', $this->getCustomer('Filter Strategy Test'));
        $this->assertFilterQuerySuccessful(['confirmed[gte]' => 'false']);
    }

    public function testFilterOperatorLte(): void
    {
        $this->createEntity('/api/customers', $this->getCustomer('Filter Strategy Test'));
        $this->assertFilterQuerySuccessful(['confirmed[lte]' => 'true']);
    }

    public function testFilterOperatorGt(): void
    {
        $this->createEntity('/api/customers', $this->getCustomer('Filter Strategy Test'));
        $this->assertFilterQuerySuccessful(['confirmed[gt]' => 'false']);
    }

    public function testFilterOperatorLt(): void
    {
        $this->createEntity('/api/customers', $this->getCustomer('Filter Strategy Test'));
        $this->assertFilterQuerySuccessful(['confirmed[lt]' => 'true']);
    }

    public function testCallableFirstParameterExtractorWithComplexTypes(): void
    {
        $data = $this->getCustomer('Parameter Extractor Test');
        $customerIri = $this->createEntity('/api/customers', $data);

        $client = self::createClient();
        $client->request('PUT', $customerIri, [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'body' => json_encode($this->buildUpdateCustomerPayload()),
        ]);
        $this->assertResponseIsSuccessful();

        $this->createEntity('/api/customer_types', ['value' => 'Parameter Test Type']);
        $this->createEntity('/api/customer_statuses', ['value' => 'Parameter Test Status']);
    }

    public function testValidationWithMutationInputValidator(): void
    {
        $invalidCustomerData = $this->buildInvalidCustomerPayload();
        $mutation = $this->buildCreateCustomerMutation();
        $response = $this->graphqlMutation($mutation, $invalidCustomerData);
        $this->assertArrayHasKey('errors', $response);
    }

    /**
     * Helper method to create GraphQL mutation request
     *
     * @param array<string, string|int|float|bool|array|null> $input
     *
     * @return array<string, string|int|float|bool|array|null>
     */
    private function graphqlMutation(string $mutation, array $input): array
    {
        $client = self::createClient();

        $payload = [
            'query' => $mutation,
            'variables' => ['input' => $input],
        ];

        $response = $client->request('POST', '/api/graphql', [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($payload),
        ]);

        return $response->toArray();
    }

    /**
     * Helper method to get customer test data
     *
     * @return array<string, string|int|float|bool|array|null>
     */
    private function getCustomer(string $initials): array
    {
        return [
            'initials' => $initials,
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'leadSource' => $this->faker->word(),
            'type' => $this->createCustomerType(),
            'status' => $this->createCustomerStatus(),
            'confirmed' => $this->faker->boolean(),
        ];
    }

    /**
     * Helper method to create customer type
     */
    private function createCustomerType(): string
    {
        $typeData = ['value' => $this->faker->word()];
        return $this->createEntity('/api/customer_types', $typeData);
    }

    /**
     * Helper method to create customer status
     */
    private function createCustomerStatus(): string
    {
        $statusData = ['value' => $this->faker->word()];
        return $this->createEntity('/api/customer_statuses', $statusData);
    }

    /**
     * @param array<string, string|int|float|bool|array|null> $data
     */
    private function assertUlidCustomerResponse(array $data, string $expectedIri): void
    {
        $this->assertArrayHasKey('@id', $data);
        $this->assertSame($expectedIri, $data['@id']);
        $this->assertMatchesRegularExpression(
            '/^\/(?:api)\/customers\/[0-9A-HJKMNP-TV-Z]{26}$/',
            $data['@id']
        );
    }

    /**
     * @return array<string, string|int|float|bool|array|null>
     */
    private function fetchResource(string $iri): array
    {
        $client = self::createClient();
        $response = $client->request('GET', $iri);
        $this->assertResponseIsSuccessful();
        return $response->toArray();
    }

    /**
     * @param array<string, string> $query
     */
    private function assertFilterQuerySuccessful(array $query): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/customers', ['query' => $query]);
        $this->assertResponseIsSuccessful();
    }

    /**
     * @return array<string, string|int|float|bool|array|null>
     */
    private function buildUpdateCustomerPayload(): array
    {
        return [
            'initials' => 'Updated Initials',
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'leadSource' => $this->faker->word(),
            'type' => $this->createCustomerType(),
            'status' => $this->createCustomerStatus(),
            'confirmed' => true,
        ];
    }

    /**
     * @return array<string, string|int|float|bool|array|null>
     */
    private function buildInvalidCustomerPayload(): array
    {
        return [
            'initials' => '',
            'email' => 'invalid-email',
            'phone' => '',
            'leadSource' => '',
            'type' => $this->createCustomerType(),
            'status' => $this->createCustomerStatus(),
            'confirmed' => true,
        ];
    }

    private function buildCreateCustomerMutation(): string
    {
        return <<<'GQL'
            mutation CreateCustomer($input: createCustomerInput!) {
                createCustomer(input: $input) {
                    customer {
                        id
                        initials
                    }
                }
            }
        GQL;
    }

    /**
     * @param array<string, string|int|float|bool|array|null> $data
     */
    private function assertResponseHasHydraOrType(array $data): void
    {
        $this->assertTrue(
            array_key_exists('hydra:member', $data) || array_key_exists('@type', $data),
            'Response should have either hydra:member or @type key'
        );
    }
}
