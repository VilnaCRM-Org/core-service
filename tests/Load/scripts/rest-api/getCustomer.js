import http from 'k6/http';
import counter from 'k6/x/counter';
import ScenarioUtils from '../../utils/scenarioUtils.js';
import Utils from '../../utils/utils.js';
import InsertCustomersUtils from '../../utils/insertCustomersUtils.js';

const scenarioName = 'getCustomer';

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

export default function getCustomer(data) {
  const customer = data.customers[counter.up() % data.customers.length];
  utils.checkCustomerIsDefined(customer);

  const { id } = customer;

  const response = http.get(`${utils.getBaseHttpUrl()}/${id}`, utils.getJsonHeader());

  utils.checkResponse(response, 'is status 200', res => res.status === 200);
}
