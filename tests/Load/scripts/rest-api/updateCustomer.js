import http from 'k6/http';
import counter from 'k6/x/counter';
import InsertCustomersUtils from '../../utils/insertCustomersUtils.js';
import ScenarioUtils from '../../utils/scenarioUtils.js';
import Utils from '../../utils/utils.js';

const scenarioName = 'updateCustomer';

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

export default function updateCustomer(data) {
  const customer = data.customers[counter.up() % data.customers.length];
  utils.checkCustomerIsDefined(customer);

  const { id } = customer;
  const generatedCustomer = utils.generateCustomer();

  const payload = JSON.stringify({
    initials: generatedCustomer.initials,
    email: generatedCustomer.email,
    phone: generatedCustomer.phone,
  });

  const response = http.patch(
    `${utils.getBaseHttpUrl()}/${id}`,
    payload,
    utils.getMergePatchHeader()
  );

  utils.checkResponse(response, 'is status 200', res => res.status === 200);
}
