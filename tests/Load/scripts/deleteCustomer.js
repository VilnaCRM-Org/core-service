import http from 'k6/http';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';

const scenarioName = 'deleteCustomer';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export function setup() {
  // Create a test customer for deleting
  const customerData = {
    initials: 'DeleteTest Customer',
    email: `deletetest_${Date.now()}@example.com`,
    phone: '+1-555-0004',
    leadSource: 'Load Test',
    confirmed: true
  };
  
  const response = utils.createCustomer(customerData);
  
  if (response.status === 201) {
    const customer = JSON.parse(response.body);
    return { customerId: customer['@id'] };
  }
  
  return { customerId: null };
}

export default function deleteCustomer(data) {
  if (!data.customerId) {
    console.log('No customer ID available for testing');
    return;
  }
  
  const response = http.del(`${utils.getBaseHttpUrl()}${data.customerId}`);
  
  utils.checkResponse(response, 'is status 204', res => res.status === 204);
}
