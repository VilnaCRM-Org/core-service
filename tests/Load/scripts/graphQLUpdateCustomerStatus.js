import http from 'k6/http';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import counter from 'k6/x/counter';

const scenarioName = 'graphQLUpdateCustomerStatus';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export function setup() {
  // Fetch existing customer statuses created by PrepareCustomers script
  const response = http.get(`${utils.getBaseHttpUrl()}/customer_statuses?itemsPerPage=100`);

  if (response.status !== 200) {
    throw new Error('Failed to fetch customer statuses for GraphQL update customer status load test.');
  }

  const data = JSON.parse(response.body);
  const statuses = data.member || [];

  if (statuses.length === 0) {
    throw new Error('No customer statuses found. Please run PrepareCustomers script first.');
  }

  return {
    statuses: statuses,
    totalStatuses: statuses.length
  };
}

export default function updateCustomerStatus(data) {
  // Use counter to select different status for each iteration
  const status = data.statuses[counter.up() % data.totalStatuses];
  const newValue = `UpdatedStatus_${Date.now()}`;
  
  // Extract ULID from IRI (e.g., "/api/customer_statuses/01K843..." -> "01K843...")
  const statusUlid = status.ulid || status['@id'].split('/').pop();

  const mutation = `
    mutation {
      updateCustomerStatus(
        input: {
          id: "${statusUlid}"
          value: "${newValue}"
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

  utils.checkResponse(
    response,
    'customer status updated',
    res => {
      if (res.status !== 200) {
        console.error(`GraphQL update failed with status ${res.status}: ${res.body}`);
        return false;
      }
      const body = JSON.parse(res.body);
      if (body.errors) {
        console.error(`GraphQL errors: ${JSON.stringify(body.errors)}`);
        return false;
      }
      
      // Debug logging
      if (!body.data) {
        console.error(`No data in response: ${JSON.stringify(body)}`);
        return false;
      }
      if (!body.data.updateCustomerStatus) {
        console.error(`No updateCustomerStatus in data: ${JSON.stringify(body.data)}`);
        return false;
      }
      if (!body.data.updateCustomerStatus.customerStatus) {
        console.error(`No customerStatus in updateCustomerStatus: ${JSON.stringify(body.data.updateCustomerStatus)}`);
        return false;
      }
      
      const returnedValue = body.data.updateCustomerStatus.customerStatus.value;
      if (returnedValue !== newValue) {
        console.error(`Value mismatch: expected "${newValue}", got "${returnedValue}"`);
        return false;
      }
      
      return true;
    }
  );
}

export function teardown(data) {
  // Statuses will be cleaned up by CleanupCustomers script
  console.log(`Tested ${data.totalStatuses} customer statuses via GraphQL update`);
}
