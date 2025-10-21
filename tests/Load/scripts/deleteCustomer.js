import http from 'k6/http';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import counter from 'k6/x/counter';

const scenarioName = 'deleteCustomer';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export function setup() {
  // Fetch existing customers created by PrepareCustomers script
  const response = http.get(`${utils.getBaseHttpUrl()}/customers?itemsPerPage=100`);

  if (response.status !== 200) {
    throw new Error('Failed to fetch customers for delete customer load test.');
  }

  const data = JSON.parse(response.body);
  const customers = data.member || [];

  if (customers.length === 0) {
    throw new Error('No customers found. Please run PrepareCustomers script first.');
  }

  return {
    customers: customers,
    totalCustomers: customers.length
  };
}

export default function deleteCustomer(data) {
  // Use counter to select different customer for each iteration
  const customerIndex = counter.up() % data.totalCustomers;
  const customer = data.customers[customerIndex];

  if (!customer) {
    console.warn(`Customer at index ${customerIndex} not found`);
    return;
  }

  const response = http.del(`http://localhost:80${customer['@id']}`);

  utils.checkResponse(response, 'is status 204', res => res.status === 204);
}

export function teardown(data) {
  // Remaining customers will be cleaned up by CleanupCustomers script
  console.log(`Deleted customers during load test from pool of ${data.totalCustomers}`);
}
