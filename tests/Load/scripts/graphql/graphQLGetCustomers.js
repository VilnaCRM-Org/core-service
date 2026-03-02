import http from 'k6/http';
import InsertCustomersUtils from '../../utils/insertCustomersUtils.js';
import ScenarioUtils from '../../utils/scenarioUtils.js';
import Utils from '../../utils/utils.js';

const scenarioName = 'graphQLGetCustomers';

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

export default function getCustomers() {
  const query = `
      query{
          customers(first: 50){
              edges {
                  node {
                      id
                  }
              }
          }
      }`;

  const response = http.post(
    utils.getBaseGraphQLUrl(),
    JSON.stringify({ query: query }),
    utils.getGraphQLHeader()
  );

  utils.checkResponse(response, '50 customers returned', res => {
    const body = JSON.parse(res.body);
    if (body.errors) {
      console.error('GraphQL errors:', JSON.stringify(body.errors));
      return false;
    }
    if (!body.data || !body.data.customers || !body.data.customers.edges) {
      console.error('Missing data in response:', JSON.stringify(body));
      return false;
    }
    return body.data.customers.edges.length === 50;
  });
}
