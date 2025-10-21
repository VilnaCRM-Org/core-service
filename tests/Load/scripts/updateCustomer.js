import http from 'k6/http';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import { randomString } from 'https://jslib.k6.io/k6-utils/1.2.0/index.js';
import counter from 'k6/x/counter';

const scenarioName = 'updateCustomer';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export function setup() {
  // Fetch existing customers created by PrepareCustomers script
  const response = http.get(`${utils.getBaseHttpUrl()}/customers?itemsPerPage=100`);

  if (response.status !== 200) {
    throw new Error('Failed to fetch customers for update customer load test.');
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

export default function updateCustomer(data) {
  // Use counter to select different customer for each iteration
  const customer = data.customers[counter.up() % data.totalCustomers];

  const updateData = {
    initials: `Updated_${randomString(8)}`,
    phone: `+1-555-${Math.floor(Math.random() * 9000) + 1000}`,
    confirmed: true,
  };

  const response = http.patch(`http://localhost:80${customer['@id']}`, JSON.stringify(updateData), {
    headers: { 'Content-Type': 'application/merge-patch+json' },
  });

  utils.checkResponse(response, 'is status 200', res => res.status === 200);
}

export function teardown(data) {
  // Customers will be cleaned up by CleanupCustomers script
  console.log(`Tested ${data.totalCustomers} customers during update operations`);
}
