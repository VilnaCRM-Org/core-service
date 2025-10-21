import http from 'k6/http';
import { check, sleep } from 'k6';
import Utils from '../utils/utils.js';

const utils = new Utils();

export const options = {
  scenarios: {
    cleanup: {
      executor: 'shared-iterations',
      iterations: 1,
      vus: 1,
      maxDuration: '10m',
    },
  },
  thresholds: {
    http_req_failed: ['rate<0.5'], // Allow higher failure rate during cleanup
    http_req_duration: ['p(95)<5000'],
  },
};

export default function cleanupCustomers() {
  console.log('Starting test data cleanup...');

  // 1. Delete all customers
  console.log('Fetching customers for deletion...');
  let customersDeleted = 0;
  let page = 1;
  let hasMore = true;

  while (hasMore) {
    const response = http.get(`${utils.getBaseHttpUrl()}/customers?page=${page}&itemsPerPage=30`);

    if (response.status !== 200) {
      console.error(`Failed to fetch customers page ${page}: ${response.status}`);
      break;
    }

    const data = JSON.parse(response.body);
    const customers = data.member || [];

    if (customers.length === 0) {
      hasMore = false;
      break;
    }

    console.log(`Processing ${customers.length} customers from page ${page}...`);

    for (const customer of customers) {
      const deleteResponse = http.del(`http://localhost:80${customer['@id']}`);

      if (deleteResponse.status === 204) {
        customersDeleted++;
      } else {
        console.warn(`Failed to delete customer ${customer['@id']}: ${deleteResponse.status}`);
      }

      // Small delay to avoid overwhelming the server
      if (customersDeleted % 10 === 0) {
        sleep(0.1);
      }
    }

    // Check if there are more pages
    hasMore = data.view && data.view.next;
    page++;
  }

  console.log(`✓ Deleted ${customersDeleted} customers`);

  // 2. Delete all customer statuses
  console.log('Fetching customer statuses for deletion...');
  let statusesDeleted = 0;
  page = 1;
  hasMore = true;

  while (hasMore) {
    const response = http.get(
      `${utils.getBaseHttpUrl()}/customer_statuses?page=${page}&itemsPerPage=30`
    );

    if (response.status !== 200) {
      console.error(`Failed to fetch statuses page ${page}: ${response.status}`);
      break;
    }

    const data = JSON.parse(response.body);
    const statuses = data.member || [];

    if (statuses.length === 0) {
      hasMore = false;
      break;
    }

    console.log(`Processing ${statuses.length} statuses from page ${page}...`);

    for (const status of statuses) {
      const deleteResponse = http.del(`http://localhost:80${status['@id']}`);

      if (deleteResponse.status === 204) {
        statusesDeleted++;
      } else {
        console.warn(`Failed to delete status ${status['@id']}: ${deleteResponse.status}`);
      }
    }

    hasMore = data['hydra:view'] && data['hydra:view']['hydra:next'];
    page++;
  }

  console.log(`✓ Deleted ${statusesDeleted} customer statuses`);

  // 3. Delete all customer types
  console.log('Fetching customer types for deletion...');
  let typesDeleted = 0;
  page = 1;
  hasMore = true;

  while (hasMore) {
    const response = http.get(
      `${utils.getBaseHttpUrl()}/customer_types?page=${page}&itemsPerPage=30`
    );

    if (response.status !== 200) {
      console.error(`Failed to fetch types page ${page}: ${response.status}`);
      break;
    }

    const data = JSON.parse(response.body);
    const types = data.member || [];

    if (types.length === 0) {
      hasMore = false;
      break;
    }

    console.log(`Processing ${types.length} types from page ${page}...`);

    for (const type of types) {
      const deleteResponse = http.del(`http://localhost:80${type['@id']}`);

      if (deleteResponse.status === 204) {
        typesDeleted++;
      } else {
        console.warn(`Failed to delete type ${type['@id']}: ${deleteResponse.status}`);
      }
    }

    hasMore = data['hydra:view'] && data['hydra:view']['hydra:next'];
    page++;
  }

  console.log(`✓ Deleted ${typesDeleted} customer types`);

  console.log(`\n=== Cleanup Summary ===`);
  console.log(`✓ Customers deleted: ${customersDeleted}`);
  console.log(`✓ Statuses deleted: ${statusesDeleted}`);
  console.log(`✓ Types deleted: ${typesDeleted}`);
  console.log(`=======================\n`);
}
