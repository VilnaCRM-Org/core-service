import http from 'k6/http';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import counter from 'k6/x/counter';

const scenarioName = 'graphQLUpdateCustomerType';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export function setup() {
  // Fetch existing customer types created by prepareCustomers script
  const response = http.get(`${utils.getBaseUrl()}/customer_types?itemsPerPage=100`);

  if (response.status !== 200) {
    throw new Error('Failed to fetch customer types for GraphQL update customer type load test.');
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

export default function updateCustomerType(data) {
  // Use counter to select different type for each iteration
  const type = data.types[counter.up() % data.totalTypes];
  const newValue = `UpdatedType_${Date.now()}`;

  // Use the full IRI for the ID
  const typeIri = type['@id'];

  const mutation = `
    mutation {
      updateCustomerType(
        input: {
          id: "${typeIri}"
          value: "${newValue}"
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

  utils.checkResponse(response, 'customer type updated', res => {
    const body = JSON.parse(res.body);
    return (
      body.data &&
      body.data.updateCustomerType &&
      body.data.updateCustomerType.customerType &&
      body.data.updateCustomerType.customerType.value === newValue
    );
  });
}

export function teardown(data) {
  // Types will be cleaned up by CleanupCustomers script
  console.log(`Tested ${data.totalTypes} customer types via GraphQL update`);
}
