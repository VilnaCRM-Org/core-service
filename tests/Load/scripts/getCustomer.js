import http from 'k6/http';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import counter from 'k6/x/counter';

const scenarioName = 'getCustomer';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export function setup() {
  // Fetch existing customers created by PrepareCustomers script
  const response = http.get(`${utils.getBaseHttpUrl()}/customers?itemsPerPage=100`);

  if (response.status !== 200) {
    throw new Error('Failed to fetch customers for get customer load test.');
  }

  const data = JSON.parse(response.body);
  const customers = data.member || [];

  if (customers.length === 0) {
    throw new Error('No customers found. Please run PrepareCustomers script first.');
  }

  return {
    customers: customers,
    totalCustomers: customers.length,
  };
}

export default function getCustomer(data) {
  // Use counter to select different customer for each iteration
  const customer = data.customers[counter.up() % data.totalCustomers];

  const response = http.get(`http://localhost:80${customer['@id']}`);

  utils.checkResponse(response, 'is status 200', res => res.status === 200);
}

export function teardown(data) {
  // Customers will be cleaned up by CleanupCustomers script
  console.log(`Tested ${data.totalCustomers} customers during get operations`);
}
