import http from 'k6/http';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';

const scenarioName = 'getCustomers';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export function setup() {
  // Create minimal customers without type/status dependencies to avoid database issues
  const customers = [];

  for (let i = 0; i < 3; i++) {
    const customerData = {
      initials: `ListTest Customer ${i}`,
      email: `listtest_${i}_${Date.now()}@example.com`,
      phone: `+1-555-000${i}`,
      leadSource: 'Load Test',
      confirmed: i % 2 === 0,
      // No type or status - keep it simple
    };

    const response = utils.createCustomer(customerData);

    if (response.status === 201) {
      const customer = JSON.parse(response.body);
      customers.push(customer['@id']);
    }
  }

  return {
    customerIds: customers,
  };
}

export default function getCustomers(data) {
  // Test the customers collection endpoint with basic pagination
  // Use simple filters that are guaranteed to work
  const filters = ['', '?page=1', '?itemsPerPage=5'];

  // Use deterministic filter selection
  const filterIndex = __ITER % filters.length;
  const filter = filters[filterIndex];

  // Always test the collection endpoint, not individual customers
  const response = http.get(`${utils.getBaseHttpUrl()}/customers${filter}`);

  // Only accept 200 - we need to fix any 500 errors
  utils.checkResponse(response, 'is status 200', res => res.status === 200);
}

export function teardown(data) {
  // Clean up test customers
  if (data.customerIds) {
    data.customerIds.forEach(customerId => {
      http.del(`${utils.getBaseHttpUrl()}${customerId}`);
    });
  }
}
