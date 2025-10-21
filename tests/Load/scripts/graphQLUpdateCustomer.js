import http from 'k6/http';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';

const scenarioName = 'graphQLUpdateCustomer';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export function setup() {
  // Create a test customer to update
  const customerTypeData = { value: `GraphQLUpdateType_${Date.now()}` };
  const typeResponse = utils.createCustomerType(customerTypeData);

  const customerStatusData = { value: `GraphQLUpdateStatus_${Date.now()}` };
  const statusResponse = utils.createCustomerStatus(customerStatusData);

  if (typeResponse.status === 201 && statusResponse.status === 201) {
    const type = JSON.parse(typeResponse.body);
    const status = JSON.parse(statusResponse.body);

    const customerData = {
      initials: `GraphQL Update Customer`,
      email: `graphql_update_${Date.now()}@example.com`,
      phone: `+1-555-7777`,
      leadSource: 'GraphQL Load Test',
      confirmed: false,
      type: type['@id'],
      status: status['@id'],
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString(),
    };

    const response = utils.createCustomer(customerData);

    if (response.status === 201) {
      const customer = JSON.parse(response.body);

      return {
        customerId: customer['@id'],
        customerEmail: customer.email,
        typeIri: type['@id'],
        statusIri: status['@id'],
      };
    }
  }

  return null;
}

export default function updateCustomer(data) {
  if (!data || !data.customerId) {
    return;
  }

  const newInitials = `Updated_Customer_${Date.now()}`;
  const now = new Date().toISOString();

  const mutation = `
    mutation {
      updateCustomer(
        input: {
          id: "${data.customerId}"
          initials: "${newInitials}"
          confirmed: true
          updatedAt: "${now}"
        }
      ) {
        customer {
          id
          initials
          email
          confirmed
          updatedAt
        }
      }
    }`;

  const response = http.post(
    utils.getBaseGraphQLUrl(),
    JSON.stringify({ query: mutation }),
    utils.getGraphQLHeader()
  );

  utils.checkResponse(response, 'customer updated', res => {
    const body = JSON.parse(res.body);
    return (
      body.data &&
      body.data.updateCustomer &&
      body.data.updateCustomer.customer &&
      body.data.updateCustomer.customer.initials === newInitials &&
      body.data.updateCustomer.customer.confirmed === true
    );
  });
}

export function teardown(data) {
  // Clean up test customer
  if (data && data.customerId) {
    http.del(`${utils.getBaseHttpUrl()}${data.customerId}`);
    http.del(`${utils.getBaseHttpUrl()}${data.typeIri}`);
    http.del(`${utils.getBaseHttpUrl()}${data.statusIri}`);
  }
}
