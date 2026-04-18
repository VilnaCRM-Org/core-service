<?php

declare(strict_types=1);

namespace App\Tests\Integration;

/**
 * Integration tests for shared infrastructure components.
 *
 * Tests infrastructure classes that support the application but might not be
 * fully covered by domain-specific tests.
 */
final class SharedInfrastructureIntegrationTest extends BaseApiCase
{
    /** Raw Schemathesis fuzz-regression payload; keep the malformed encodings intact. */
    private const SCHEMATHESIS_MALFORMED_LOOKUP_QUERY_PARTS = [
        '?%C3%A9%C2%A3%C2%87=%5BTrue%5D',
        '&%C3%A9%C2%A3%C2%87=%5B%27__main__%27%2C+None%2C+22610%5D',
        '&%C3%A9%C2%A3%C2%87=%7B%277%5Cx7f%27%3A+%27X%C3%B1%5CU000e1810%5CU0010aa72%C3%B0%F0%98%97%89%27%7D',
        '&%C3%A9%C2%A3%C2%87=%5B-1.7976931348623157e%2B308%2C+True%5D',
        '&%C3%A9%C2%A3%C2%87=z%C3%8Crm%C2%98%F0%BF%BE%8E%0F%C3%AAL%C2%9F%C2%B8%15',
        '&%C3%A9%C2%A3%C2%87=m%1B',
        '&%C3%A9%C2%A3%C2%87=',
        '&value=',
        '&ulid%5Bgte%5D=%F3%95%8B%AB%C3%88%C2%8E%2A%F0%9B%BA%9C%C3%A8',
        '&ulid%5Blt%5D=%10',
        '&%27%C2%9D=',
        '&ulid%5Bgt%5D=.exe',
        '&%0Fu%C3%900wm%C2%B3=None',
        '&%0Fu%C3%900wm%C2%B3=False',
        '&ulid%5Blte%5D=%C2%88n%C2%AF',
        '&page=-112',
        '&order%5Bulid%5D=asc',
        '&%C3%AD2Z%3E%F3%B2%AA%A03=%1F%C3%97%7C%12~%F3%93%A3%A4%15',
        '&%C3%AD2Z%3E%F3%B2%AA%A03=F',
        '&%C3%AD2Z%3E%F3%B2%AA%A03=%C3%9Bn',
        '&itemsPerPage=3',
        '&U%C3%89%5B%7F%12%23k%0E%2F%F1%A2%9E%84%5E=%F3%8A%A9%81M%C2%BB%F2%BB%B4%88%C2%BBB%28%C2%A1%C2%ACH%C2%A1%C2%B0%03%C2%8F%C2%95%C2%A7',
        '&U%C3%89%5B%7F%12%23k%0E%2F%F1%A2%9E%84%5E=%2Bi%1A%C3%9A%C3%BF4%1A%21q%16%C3%A4%24%F0%9D%BA%99%C2%90%C2%8C%F1%AD%AF%97',
        '&U%C3%89%5B%7F%12%23k%0E%2F%F1%A2%9E%84%5E=%0D',
        '&%C2%A80H%F3%93%9E%9D%C2%89%F2%B1%B9%81%0C%C2%AF%C2%8C%C2%BD=',
        '&order%5Bvalue%5D=desc',
        '&ulid%5Bbetween%5D=c%C2%98%C3%91%C3%A2%C2%AF%C3%97',
    ];

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

    public function testUnmatchedApiRouteReturnsProblemJson(): void
    {
        $client = self::createClient();
        $client->request('PATCH', '/api/__missing_route__', [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'body' => json_encode(['email' => $this->faker->unique()->safeEmail()], JSON_THROW_ON_ERROR),
        ]);

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame(
            'content-type',
            'application/problem+json; charset=utf-8'
        );

        $error = $client->getResponse()->toArray(false);

        $this->assertSame('An error occurred', $error['title']);
        $this->assertSame('Not Found', $error['detail']);
        $this->assertSame(404, $error['status']);
        $this->assertSame('/errors/404', $error['type']);
    }

    public function testInvalidUlidFilterDoesNotCrashCustomerStatusCollection(): void
    {
        $validUlid = basename($this->createEntity('/api/customer_statuses', [
            'value' => $this->faker->word(),
        ]));

        $this->assertInvalidUlidCollectionFilterIsIgnored('/api/customer_statuses', $validUlid);
    }

    public function testInvalidUlidFilterDoesNotCrashCustomerTypeCollection(): void
    {
        $validUlid = basename($this->createEntity('/api/customer_types', [
            'value' => $this->faker->word(),
        ]));

        $this->assertInvalidUlidCollectionFilterIsIgnored('/api/customer_types', $validUlid);
    }

    /**
     * @dataProvider malformedLookupCollectionPathProvider
     */
    public function testSchemathesisMalformedLookupQueryDoesNotCrashCollection(
        string $resourcePath
    ): void {
        $this->createEntity($resourcePath, ['value' => $this->faker->word()]);

        $client = self::createClient();
        $response = $client->request(
            'GET',
            $resourcePath . implode('', self::SCHEMATHESIS_MALFORMED_LOOKUP_QUERY_PARTS)
        );

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame(
            'content-type',
            'application/problem+json; charset=utf-8'
        );
        $this->assertStringContainsString(
            'Page should not be less than 1',
            (string) $response->toArray(false)['detail']
        );
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
     * @return array<string, array{0: string}>
     */
    public static function malformedLookupCollectionPathProvider(): array
    {
        return [
            'customer statuses' => ['/api/customer_statuses'],
            'customer types' => ['/api/customer_types'],
        ];
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
            '/^\\/(?:api)\\/customers\\/[0-9A-HJKMNP-TV-Z]{26}$/',
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

    private function assertInvalidUlidCollectionFilterIsIgnored(
        string $resourcePath,
        string $validUlid
    ): void {
        $client = self::createClient();
        $response = $client->request('GET', $resourcePath, [
            'query' => [
                'ulid[gte]' => $validUlid,
                'ulid[lte]' => $validUlid,
                'ulid[lt]' => '😍',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $collection = $data['member'] ?? $data['hydra:member'] ?? [];
        $this->assertResponseHasHydraOrType($data);
        self::assertContains($validUlid, array_column($collection, 'ulid'));
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
