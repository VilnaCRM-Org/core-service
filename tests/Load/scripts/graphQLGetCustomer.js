import http from 'k6/http';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';

const scenarioName = 'graphQLGetCustomer';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export function setup() {
  // Create a test customer
  const customerTypeData = { value: `GraphQLGetType_${Date.now()}` };
  const typeResponse = utils.createCustomerType(customerTypeData);

  const customerStatusData = { value: `GraphQLGetStatus_${Date.now()}` };
  const statusResponse = utils.createCustomerStatus(customerStatusData);

  if (typeResponse.status === 201 && statusResponse.status === 201) {
    const type = JSON.parse(typeResponse.body);
    const status = JSON.parse(statusResponse.body);

    const customerData = {
      initials: `GraphQL Get Customer`,
      email: `graphql_get_${Date.now()}@example.com`,
      phone: `+1-555-8888`,
      leadSource: 'GraphQL Load Test',
      confirmed: true,
      type: type['@id'],
      status: status['@id'],
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString()
    };

    const response = utils.createCustomer(customerData);

    if (response.status === 201) {
      const customer = JSON.parse(response.body);
      // Extract the ID from the IRI (e.g., /api/customers/01234 -> 01234)
      const customerId = customer['@id'].split('/').pop();

      return {
        customerId: customerId,
        customerIri: customer['@id'],
        typeIri: type['@id'],
        statusIri: status['@id']
      };
    }
  }

  return null;
}

export default function getCustomer(data) {
  if (!data || !data.customerId) {
    return;
  }

  const query = `
    query {
      customer(id: "/api/customers/${data.customerId}") {
        id
        initials
        email
        phone
        leadSource
        confirmed
        type {
          id
          value
        }
        status {
          id
          value
        }
        createdAt
        updatedAt
      }
    }`;

  const response = http.post(
    utils.getBaseGraphQLUrl(),
    JSON.stringify({ query: query }),
    utils.getGraphQLHeader()
  );

  utils.checkResponse(
    response,
    'customer query returned',
    res => {
      const body = JSON.parse(res.body);
      return body.data && body.data.customer && body.data.customer.id;
    }
  );
}

export function teardown(data) {
  // Clean up test customer
  if (data && data.customerIri) {
    http.del(`${utils.getBaseHttpUrl()}${data.customerIri}`);
    http.del(`${utils.getBaseHttpUrl()}${data.typeIri}`);
    http.del(`${utils.getBaseHttpUrl()}${data.statusIri}`);
  }
}
