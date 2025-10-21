import http from 'k6/http';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';

const scenarioName = 'graphQLDeleteCustomer';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export function setup() {
  // Create customers to delete
  const customerTypeData = { value: `GraphQLDeleteType_${Date.now()}` };
  const typeResponse = utils.createCustomerType(customerTypeData);

  const customerStatusData = { value: `GraphQLDeleteStatus_${Date.now()}` };
  const statusResponse = utils.createCustomerStatus(customerStatusData);

  const customersToDelete = [];

  if (typeResponse.status === 201 && statusResponse.status === 201) {
    const type = JSON.parse(typeResponse.body);
    const status = JSON.parse(statusResponse.body);

    // Create multiple customers for deletion during test
    for (let i = 0; i < 5; i++) {
      const customerData = {
        initials: `GraphQL Delete Customer ${i}`,
        email: `graphql_delete_${i}_${Date.now()}@example.com`,
        phone: `+1-555-666${i}`,
        leadSource: 'GraphQL Load Test',
        confirmed: true,
        type: type['@id'],
        status: status['@id'],
        createdAt: new Date().toISOString(),
        updatedAt: new Date().toISOString(),
      };

      const response = utils.createCustomer(customerData);

      if (response.status === 201) {
        const customer = JSON.parse(response.body);
        customersToDelete.push(customer['@id']);
      }
    }

    return {
      customersToDelete,
      typeIri: type['@id'],
      statusIri: status['@id'],
    };
  }

  return { customersToDelete: [] };
}

export default function deleteCustomer(data) {
  if (!data || !data.customersToDelete || data.customersToDelete.length === 0) {
    return;
  }

  // Get a customer to delete (cycle through the list)
  const customerIndex = __ITER % data.customersToDelete.length;
  const customerId = data.customersToDelete[customerIndex];

  const mutation = `
    mutation {
      deleteCustomer(
        input: {
          id: "${customerId}"
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

  utils.checkResponse(response, 'customer deleted', res => {
    const body = JSON.parse(res.body);
    return body.data && body.data.deleteCustomer && body.data.deleteCustomer.customer;
  });
}

export function teardown(data) {
  // Clean up type and status (customers should be deleted during test)
  if (data) {
    // Try to delete any remaining customers
    if (data.customersToDelete) {
      data.customersToDelete.forEach(customerId => {
        try {
          http.del(`${utils.getBaseHttpUrl()}${customerId}`);
        } catch (e) {
          // Customer might already be deleted, ignore error
        }
      });
    }

    if (data.typeIri) {
      http.del(`${utils.getBaseHttpUrl()}${data.typeIri}`);
    }

    if (data.statusIri) {
      http.del(`${utils.getBaseHttpUrl()}${data.statusIri}`);
    }
  }
}
