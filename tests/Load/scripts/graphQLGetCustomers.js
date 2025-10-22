import http from 'k6/http';
import InsertCustomersUtils from '../utils/insertCustomersUtils.js';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';

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

  utils.checkResponse(
    response,
    '50 customers returned',
    res => JSON.parse(res.body).data.customers.edges.length === 50
  );
}
