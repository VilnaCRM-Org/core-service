import http from 'k6/http';
import ScenarioUtils from '../../utils/scenarioUtils.js';
import Utils from '../../utils/utils.js';
import { randomString } from 'https://jslib.k6.io/k6-utils/1.2.0/index.js';

const scenarioName = 'graphQLCreateCustomerType';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export function setup() {
  return { createdTypes: [] };
}

export default function createCustomerType(data) {
  const value = `GraphQL_Type_${randomString(8)}`;

  const mutation = `
    mutation {
      createCustomerType(
        input: {
          value: "${value}"
        }
      ) {
        customerType {
          id
          value
        }
      }
    }`;

  const response = http.post(
    utils.getBaseGraphQLUrl(),
    JSON.stringify({ query: mutation }),
    utils.getGraphQLHeader()
  );

  utils.checkResponse(response, 'customer type created', res => {
    const body = JSON.parse(res.body);
    if (body.errors) {
      console.error('GraphQL errors:', JSON.stringify(body.errors));
      return false;
    }
    if (!body.data || !body.data.createCustomerType || !body.data.createCustomerType.customerType) {
      console.error('Missing data in response:', JSON.stringify(body));
      return false;
    }
    // Track created type for cleanup
    data.createdTypes.push(body.data.createCustomerType.customerType.id);
    return body.data.createCustomerType.customerType.value === value;
  });
}

export function teardown(data) {
  // Clean up created types
  if (data.createdTypes) {
    data.createdTypes.forEach(typeId => {
      http.del(`${utils.getBaseDomain()}${typeId}`);
    });
  }
}
