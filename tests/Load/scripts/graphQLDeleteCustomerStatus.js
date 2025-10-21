import http from 'k6/http';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import counter from 'k6/x/counter';

const scenarioName = 'graphQLDeleteCustomerStatus';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export function setup() {
  // Create test customer statuses specifically for deletion
  // Create enough for smoke test: 5 rps * 10s = 50 iterations
  const statuses = [];

  for (let i = 0; i < 60; i++) {
    const statusData = {
      value: `GraphQLDeleteTestStatus_${i}_${Date.now()}`,
    };

    const response = utils.createCustomerStatus(statusData);

    if (response.status === 201) {
      const status = JSON.parse(response.body);
      statuses.push(status);
    }
  }

  return {
    statuses: statuses,
    totalStatuses: statuses.length,
  };
}

export default function deleteCustomerStatus(data) {
  // Use counter to select different status for each iteration
  const statusIndex = counter.up() % data.totalStatuses;
  const status = data.statuses[statusIndex];

  if (!status) {
    console.warn(`Customer status at index ${statusIndex} not found`);
    return;
  }

  const mutation = `
    mutation {
      deleteCustomerStatus(
        input: {
          id: "${status['@id']}"
        }
      ) {
        customerStatus {
          id
        }
      }
    }`;

  const response = http.post(
    utils.getBaseGraphQLUrl(),
    JSON.stringify({ query: mutation }),
    utils.getGraphQLHeader()
  );

  utils.checkResponse(response, 'customer status deleted', res => {
    if (res.status !== 200) {
      console.error(`GraphQL delete failed with status ${res.status}: ${res.body}`);
      return false;
    }
    const body = JSON.parse(res.body);
    if (body.errors) {
      console.error(`GraphQL errors: ${JSON.stringify(body.errors)}`);
      return false;
    }
    return (
      body.data && body.data.deleteCustomerStatus && body.data.deleteCustomerStatus.customerStatus
    );
  });
}

export function teardown(data) {
  console.log(
    `Deleted customer statuses during GraphQL load test from pool of ${data.totalStatuses}`
  );
}
