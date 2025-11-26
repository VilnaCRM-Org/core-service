import http from 'k6/http';
import ScenarioUtils from '../../utils/scenarioUtils.js';
import Utils from '../../utils/utils.js';
import counter from 'k6/x/counter';

const scenarioName = 'graphQLGetCustomerType';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export function setup() {
  // Fetch existing customer types created by prepareCustomers script
  const response = http.get(`${utils.getBaseUrl()}/customer_types?itemsPerPage=100`);

  if (response.status !== 200) {
    throw new Error('Failed to fetch customer types for GraphQL get customer type load test.');
  }

  const data = JSON.parse(response.body);
  const types = data.member || [];

  if (types.length === 0) {
    throw new Error('No customer types found. Please run prepareCustomers script first.');
  }

  return {
    types: types,
    totalTypes: types.length,
  };
}

export default function getCustomerType(data) {
  // Use counter to select different type for each iteration
  const type = data.types[counter.up() % data.totalTypes];
  const typeId = type['@id'].split('/').pop();

  const query = `
    query {
      customerType(id: "${type['@id']}") {
        id
        value
      }
    }`;

  const response = http.post(
    utils.getBaseGraphQLUrl(),
    JSON.stringify({ query: query }),
    utils.getGraphQLHeader()
  );

  utils.checkResponse(response, 'customerType query returned', res => {
    const body = JSON.parse(res.body);
    if (body.errors) {
      console.error('GraphQL errors:', JSON.stringify(body.errors));
      return false;
    }
    if (!body.data || !body.data.customerType) {
      console.error('Missing data in response:', JSON.stringify(body));
      return false;
    }
    return body.data.customerType.id !== undefined;
  });
}

export function teardown(data) {
  // Types will be cleaned up by CleanupCustomers script
  console.log(`Tested ${data.totalTypes} customer types via GraphQL get single type`);
}
