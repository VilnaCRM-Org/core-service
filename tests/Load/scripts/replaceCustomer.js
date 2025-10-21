import http from 'k6/http';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import { randomString } from 'https://jslib.k6.io/k6-utils/1.2.0/index.js';

const scenarioName = 'replaceCustomer';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export function setup() {
  // Create a test customer for replacing
  const customerData = {
    initials: 'ReplaceTest Customer',
    email: `replacetest_${Date.now()}@example.com`,
    phone: '+1-555-0003',
    leadSource: 'Load Test',
    confirmed: false,
  };

  const response = utils.createCustomer(customerData);

  if (response.status === 201) {
    const customer = JSON.parse(response.body);
    return { customerId: customer['@id'] };
  }

  return { customerId: null };
}

export default function replaceCustomer(data) {
  if (!data.customerId) {
    console.log('No customer ID available for testing');
    return;
  }

  const domains = ['example.com', 'test.org', 'demo.net'];
  const leadSources = ['Website', 'Referral', 'Social Media'];
  const name = `Replaced_${randomString(8)}`;
  const domain = domains[Math.floor(Math.random() * domains.length)];

  const replaceData = {
    initials: name,
    email: `${name.toLowerCase()}@${domain}`,
    phone: `+1-555-${Math.floor(Math.random() * 9000) + 1000}`,
    leadSource: leadSources[Math.floor(Math.random() * leadSources.length)],
    confirmed: true,
  };

  const response = http.put(
    `${utils.getBaseHttpUrl()}${data.customerId}`,
    JSON.stringify(replaceData),
    {
      headers: { 'Content-Type': 'application/ld+json' },
    }
  );

  utils.checkResponse(response, 'is status 200', res => res.status === 200);
}

export function teardown(data) {
  // Clean up the test customer
  if (data.customerId) {
    http.del(`${utils.getBaseHttpUrl()}${data.customerId}`);
  }
}
