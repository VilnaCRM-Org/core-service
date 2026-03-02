import http from 'k6/http';
import counter from 'k6/x/counter';
import InsertCustomersUtils from '../../utils/insertCustomersUtils.js';
import ScenarioUtils from '../../utils/scenarioUtils.js';
import Utils from '../../utils/utils.js';

const scenarioName = 'graphQLGetCustomer';

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

  const id = utils.getGraphQLIdPrefix() + customer.id;

  const query = `
      query{
          customer(id: "${id}"){
              id
          }
      }`;

  const response = http.post(
    utils.getBaseGraphQLUrl(),
    JSON.stringify({ query: query }),
    utils.getGraphQLHeader()
  );

  utils.checkResponse(response, 'customer returned', res => {
    const body = JSON.parse(res.body);
    if (body.errors) {
      console.error('GraphQL errors:', JSON.stringify(body.errors));
      return false;
    }
    if (!body.data || !body.data.customer) {
      console.error('Missing data in response:', JSON.stringify(body));
      return false;
    }
    return body.data.customer.id === id;
  });
}
