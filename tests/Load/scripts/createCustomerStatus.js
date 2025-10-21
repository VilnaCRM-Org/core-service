import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import { randomString } from 'https://jslib.k6.io/k6-utils/1.2.0/index.js';

const scenarioName = 'createCustomerStatus';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export default function createCustomerStatus() {
  const statusData = generateCustomerStatusData();

  const response = utils.createCustomerStatus(statusData);

  utils.checkResponse(response, 'is status 201', res => res.status === 201);
}

function generateCustomerStatusData() {
  const statusNames = ['Active', 'Inactive', 'Lead', 'Prospect', 'Converted', 'Churned', 'Pending'];

  return {
    value: `${statusNames[Math.floor(Math.random() * statusNames.length)]}_${randomString(6)}`,
  };
}
