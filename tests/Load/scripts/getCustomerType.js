import http from 'k6/http';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';

const scenarioName = 'getCustomerType';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export function setup() {
  // Create a test customer type for getting
  const typeData = {
    value: `GetTestType_${Date.now()}`,
  };

  const response = utils.createCustomerType(typeData);

  if (response.status === 201) {
    const type = JSON.parse(response.body);
    return { typeId: type['@id'] };
  }

  return { typeId: null };
}

export default function getCustomerType(data) {
  if (!data.typeId) {
    console.log('No customer type ID available for testing');
    return;
  }

  // Extract just the ID part from the IRI
  const typeId = data.typeId.replace('/api', '');
  const response = http.get(`${utils.getBaseHttpUrl()}${typeId}`);

  utils.checkResponse(response, 'is status 200', res => res.status === 200);
}

export function teardown(data) {
  // Clean up the test customer type
  if (data.typeId) {
    const typeId = data.typeId.replace('/api', '');
    http.del(`${utils.getBaseHttpUrl()}${typeId}`);
  }
}
