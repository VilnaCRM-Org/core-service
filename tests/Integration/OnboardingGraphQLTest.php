<?php

declare(strict_types=1);

namespace App\Tests\Integration;

final class OnboardingGraphQLTest extends BaseGraphQLCase
{
    public function testCreateAndQueryOnboardingStep(): void
    {
        $response = $this->graphqlMutation(
            <<<'GRAPHQL'
            mutation CreateOnboardingStep($input: createOnboardingStepInput!) {
              createOnboardingStep(input: $input) {
                onboardingStep {
                  id
                  code
                  label
                  position
                  enabled
                }
              }
            }
            GRAPHQL,
            [
                'code' => 'tariff_plan',
                'label' => 'Tariff plan',
                'position' => 1,
                'enabled' => true,
            ]
        );

        $this->assertGraphQLSuccess($response);
        $step = $response['data']['createOnboardingStep']['onboardingStep'];
        $this->assertSame('tariff_plan', $step['code']);

        $queryResponse = $this->graphqlRequest(
            <<<'GRAPHQL'
            query GetOnboardingStep($id: ID!) {
              onboardingStep(id: $id) {
                id
                code
                label
              }
            }
            GRAPHQL,
            ['id' => $step['id']]
        );

        $this->assertGraphQLSuccess($queryResponse);
        $this->assertSame(
            'Tariff plan',
            $queryResponse['data']['onboardingStep']['label']
        );
    }

    public function testCreateAndQueryTariffPlan(): void
    {
        $response = $this->graphqlMutation(
            <<<'GRAPHQL'
            mutation CreateTariffPlan($input: createTariffPlanInput!) {
              createTariffPlan(input: $input) {
                tariffPlan {
                  id
                  code
                  name
                  priceCents
                  pricePeriod
                }
              }
            }
            GRAPHQL,
            [
                'code' => 'free',
                'name' => 'Free rate',
                'description' => 'Cloud solution with no functional limitations.',
                'deploymentOptions' => ['cloud'],
                'functionalLimitations' => false,
                'userLimit' => 50,
                'priceCents' => 0,
                'priceCurrency' => 'USD',
                'pricePeriod' => 'none',
                'position' => 1,
                'enabled' => true,
            ]
        );

        $this->assertGraphQLSuccess($response);
        $plan = $response['data']['createTariffPlan']['tariffPlan'];
        $this->assertSame('free', $plan['code']);

        $queryResponse = $this->graphqlRequest(
            <<<'GRAPHQL'
            query GetTariffPlan($id: ID!) {
              tariffPlan(id: $id) {
                id
                code
                name
              }
            }
            GRAPHQL,
            ['id' => $plan['id']]
        );

        $this->assertGraphQLSuccess($queryResponse);
        $this->assertSame('Free rate', $queryResponse['data']['tariffPlan']['name']);
    }
}
