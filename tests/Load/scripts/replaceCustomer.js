import http from 'k6/http';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import { randomString } from 'https://jslib.k6.io/k6-utils/1.2.0/index.js';
import counter from 'k6/x/counter';

const scenarioName = 'replaceCustomer';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export function setup() {
  // Fetch existing customers created by PrepareCustomers script
  const response = http.get(`${utils.getBaseHttpUrl()}/customers?itemsPerPage=100`);

  if (response.status !== 200) {
    throw new Error(`Failed to fetch customers: HTTP ${response.status}`);
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

export default function replaceCustomer(data) {
  // Use counter to select different customer for each iteration
  const customer = data.customers[counter.up() % data.totalCustomers];

  const domains = ['example.com', 'test.org', 'demo.net'];
  const leadSources = ['Website', 'Referral', 'Social Media'];
  const name = `Replaced_${randomString(8)}`;
  const domain = domains[Math.floor(Math.random() * domains.length)];

  const replaceData = {
    initials: name,
    email: `${name.toLowerCase()}@${domain}`,
    phone: `+1-555-${Math.floor(Math.random() * 9000) + 1000}`,
    leadSource: leadSources[Math.floor(Math.random() * leadSources.length)],
    type: customer.type['@id'],
    status: customer.status['@id'],
    confirmed: true,
  };

  const response = http.put(`http://localhost:80${customer['@id']}`, JSON.stringify(replaceData), {
    headers: { 'Content-Type': 'application/ld+json' },
  });

  utils.checkResponse(response, 'is status 200', res => res.status === 200);
}

export function teardown(data) {
  // Customers will be cleaned up by CleanupCustomers script
  console.log(`Tested ${data.totalCustomers} customers during replace operations`);
}
