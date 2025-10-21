import http from 'k6/http';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';

const scenarioName = 'getCustomerTypes';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export function setup() {
  // Create some test customer types for listing
  const types = [];
  
  for (let i = 0; i < 3; i++) {
    const typeData = {
      value: `ListTestType_${i}_${Date.now()}`
    };
    
    const response = utils.createCustomerType(typeData);
    
    if (response.status === 201) {
      const type = JSON.parse(response.body);
      types.push(type['@id']);
    }
  }
  
  return { typeIds: types };
}

export default function getCustomerTypes() {
  // Test different pagination and filtering options
  const filters = [
    '',
    '?page=1',
    '?itemsPerPage=10',
    '?order[ulid]=desc',
    '?order[value]=asc'
  ];
  
  const filter = filters[Math.floor(Math.random() * filters.length)];
  const response = http.get(`${utils.getBaseHttpUrl()}/customer_types${filter}`);
  
  utils.checkResponse(response, 'is status 200', res => res.status === 200);
}

export function teardown(data) {
  // Clean up test customer types
  if (data.typeIds) {
    data.typeIds.forEach(typeId => {
      http.del(`${utils.getBaseHttpUrl()}${typeId}`);
    });
  }
}
