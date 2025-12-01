import http from 'k6/http';
import counter from 'k6/x/counter';
import InsertCustomersUtils from '../../utils/insertCustomersUtils.js';
import ScenarioUtils from '../../utils/scenarioUtils.js';
import Utils from '../../utils/utils.js';

const scenarioName = 'deleteCustomer';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const insertCustomersUtils = new InsertCustomersUtils(utils, scenarioName);

const customers = insertCustomersUtils.loadInsertedCustomers();

export function setup() {
  return {
    customers: customers,
  };
}

export const options = scenarioUtils.getOptions();

export default function deleteCustomer(data) {
  const customer = data.customers[counter.up() % data.customers.length];
  utils.checkCustomerIsDefined(customer);

  const { id } = customer;

  const response = http.del(`${utils.getBaseHttpUrl()}/${id}`, null, utils.getJsonHeader());

  utils.checkResponse(response, 'is status 204', res => res.status === 204);
}
