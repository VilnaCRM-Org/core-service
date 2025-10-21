import http from 'k6/http';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import { randomString } from 'https://jslib.k6.io/k6-utils/1.2.0/index.js';

const scenarioName = 'updateCustomer';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export function setup() {
  // Create a test customer for updating
  const customerData = {
    initials: 'UpdateTest Customer',
    email: `updatetest_${Date.now()}@example.com`,
    phone: '+1-555-0002',
    leadSource: 'Load Test',
    confirmed: false
  };
  
  const response = utils.createCustomer(customerData);
  
  if (response.status === 201) {
    const customer = JSON.parse(response.body);
    return { customerId: customer['@id'] };
  }
  
  return { customerId: null };
}

export default function updateCustomer(data) {
  if (!data.customerId) {
    console.log('No customer ID available for testing');
    return;
  }
  
  const updateData = {
    initials: `Updated_${randomString(8)}`,
    phone: `+1-555-${Math.floor(Math.random() * 9000) + 1000}`,
    confirmed: true
  };
  
  const response = http.patch(
    `${utils.getBaseHttpUrl()}${data.customerId}`,
    JSON.stringify(updateData),
    {
      headers: { 'Content-Type': 'application/merge-patch+json' }
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
