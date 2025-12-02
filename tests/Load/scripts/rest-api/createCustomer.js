import ScenarioUtils from '../../utils/scenarioUtils.js';
import Utils from '../../utils/utils.js';
import InsertCustomersUtils from '../../utils/insertCustomersUtils.js';

const scenarioName = 'createCustomer';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const insertCustomersUtils = new InsertCustomersUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export function setup() {
  // Pre-create types and statuses for use during the test
  const types = insertCustomersUtils.insertCustomerTypes();
  const statuses = insertCustomersUtils.insertCustomerStatuses();

  return {
    types,
    statuses,
  };
}

export default function createCustomer(data) {
  const customerData = utils.generateCustomer(data.types, data.statuses);
  const response = utils.createCustomer(customerData);

  utils.checkResponse(response, 'is status 201', res => res.status === 201);
}
