import http from 'k6/http';
import ScenarioUtils from '../../utils/scenarioUtils.js';
import Utils from '../../utils/utils.js';
import { randomString } from 'https://jslib.k6.io/k6-utils/1.2.0/index.js';

const scenarioName = 'graphQLCreateCustomerStatus';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export function setup() {
  return { createdStatuses: [] };
}

export default function createCustomerStatus(data) {
  const value = `GraphQL_Status_${randomString(8)}`;

  const mutation = `
    mutation {
      createCustomerStatus(
        input: {
          value: "${value}"
        }
      ) {
        customerStatus {
          id
          value
          ulid
        }
      }
    }`;

  const response = http.post(
    utils.getBaseGraphQLUrl(),
    JSON.stringify({ query: mutation }),
    utils.getGraphQLHeader()
  );

  utils.checkResponse(response, 'customer status created', res => {
    const body = JSON.parse(res.body);
    if (
      body.data &&
      body.data.createCustomerStatus &&
      body.data.createCustomerStatus.customerStatus
    ) {
      // Track created status for cleanup
      data.createdStatuses.push(body.data.createCustomerStatus.customerStatus.id);
      return body.data.createCustomerStatus.customerStatus.value === value;
    }
    return false;
  });
}

export function teardown(data) {
  // Clean up created statuses
  if (data.createdStatuses) {
    data.createdStatuses.forEach(statusId => {
      http.del(`${utils.getBaseDomain()}${statusId}`);
    });
  }
}
