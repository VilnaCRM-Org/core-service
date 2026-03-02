import http from 'k6/http';
import counter from 'k6/x/counter';
import InsertCustomersUtils from '../../utils/insertCustomersUtils.js';
import ScenarioUtils from '../../utils/scenarioUtils.js';
import Utils from '../../utils/utils.js';

const scenarioName = 'replaceCustomer';

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

export default function replaceCustomer(data) {
  const customer = data.customers[counter.up() % data.customers.length];
  utils.checkCustomerIsDefined(customer);

  const id = customer.id;
  const generatedCustomer = utils.generateCustomer();

  const payload = JSON.stringify({
    initials: generatedCustomer.initials,
    email: generatedCustomer.email,
    phone: generatedCustomer.phone,
    leadSource: generatedCustomer.leadSource,
    type: customer.type['@id'],
    status: customer.status['@id'],
    confirmed: generatedCustomer.confirmed,
  });

  const response = http.put(`${utils.getBaseHttpUrl()}/${id}`, payload, utils.getJsonHeader());

  utils.checkResponse(response, 'is status 200', res => res.status === 200);
}
