import http from 'k6/http';
import counter from 'k6/x/counter';
import InsertCustomersUtils from '../../utils/insertCustomersUtils.js';
import ScenarioUtils from '../../utils/scenarioUtils.js';
import Utils from '../../utils/utils.js';

const scenarioName = 'graphQLUpdateCustomer';

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

  const id = utils.getGraphQLIdPrefix() + customer.id;
  const mutationName = 'updateCustomer';
  const generatedCustomer = utils.generateCustomer();

  const mutation = `
     mutation {
        ${mutationName}(
            input: {
                id: "${id}"
                email: "${generatedCustomer.email}"
                initials: "${generatedCustomer.initials}"
                phone: "${generatedCustomer.phone}"
            }
        ) {
            customer {
                id
            }
        }
    }`;

  const response = http.post(
    utils.getBaseGraphQLUrl(),
    JSON.stringify({ query: mutation }),
    utils.getGraphQLHeader()
  );

  utils.checkResponse(
    response,
    'updated customer returned',
    res => JSON.parse(res.body).data[mutationName].customer.id === `${id}`
  );
}
