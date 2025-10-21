import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import { randomString } from 'https://jslib.k6.io/k6-utils/1.2.0/index.js';

const scenarioName = 'createCustomerType';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export default function createCustomerType() {
  const typeData = generateCustomerTypeData();
  
  const response = utils.createCustomerType(typeData);
  
  utils.checkResponse(response, 'is status 201', res => res.status === 201);
}

function generateCustomerTypeData() {
  const typeNames = ['Premium', 'Standard', 'VIP', 'Enterprise', 'Starter', 'Professional', 'Basic'];
  
  return {
    value: `${typeNames[Math.floor(Math.random() * typeNames.length)]}_${randomString(6)}`
  };
}
