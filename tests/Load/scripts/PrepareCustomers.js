import http from 'k6/http';
import { check } from 'k6';
import Utils from '../utils/utils.js';

const utils = new Utils();

export const options = {
  scenarios: {
    prepare: {
      executor: 'shared-iterations',
      iterations: 1,
      vus: 1,
      maxDuration: '5m',
    },
  },
  thresholds: {
    http_req_failed: ['rate<0.01'],
    http_req_duration: ['p(95)<2000'],
  },
};

export default function prepareCustomers() {
  console.log('Starting test data preparation...');

  // 1. Create customer types
  console.log('Creating customer types...');
  const types = [
    { value: 'Premium' },
    { value: 'Standard' },
    { value: 'Enterprise' },
    { value: 'Basic' },
    { value: 'VIP' },
  ];

  const createdTypes = [];
  for (const typeData of types) {
    const response = utils.createCustomerType(typeData);
    check(response, {
      'customer type created': (r) => r.status === 201,
    });

    if (response.status === 201) {
      const type = JSON.parse(response.body);
      createdTypes.push(type);
      console.log(`✓ Created customer type: ${typeData.value} (${type['@id']})`);
    } else {
      console.error(`✗ Failed to create customer type: ${typeData.value}`);
    }
  }

  // 2. Create customer statuses
  console.log('Creating customer statuses...');
  const statuses = [
    { value: 'Active' },
    { value: 'Inactive' },
    { value: 'Pending' },
    { value: 'Suspended' },
    { value: 'Archived' },
  ];

  const createdStatuses = [];
  for (const statusData of statuses) {
    const response = utils.createCustomerStatus(statusData);
    check(response, {
      'customer status created': (r) => r.status === 201,
    });

    if (response.status === 201) {
      const status = JSON.parse(response.body);
      createdStatuses.push(status);
      console.log(`✓ Created customer status: ${statusData.value} (${status['@id']})`);
    } else {
      console.error(`✗ Failed to create customer status: ${statusData.value}`);
    }
  }

  // 3. Create sample customers
  console.log('Creating sample customers...');
  const customerCount = 20;
  let customersCreated = 0;

  for (let i = 0; i < customerCount; i++) {
    const typeIndex = i % createdTypes.length;
    const statusIndex = i % createdStatuses.length;

    const customerData = {
      initials: `LoadTest${i}`,
      email: `loadtest${i}_${Date.now()}@example.com`,
      phone: `+1-555-${String(1000 + i).padStart(4, '0')}`,
      leadSource: ['Website', 'Referral', 'Social Media', 'Email Campaign'][i % 4],
      confirmed: i % 2 === 0,
      type: createdTypes[typeIndex]['@id'],
      status: createdStatuses[statusIndex]['@id'],
    };

    const response = utils.createCustomer(customerData);
    check(response, {
      'customer created': (r) => r.status === 201,
    });

    if (response.status === 201) {
      customersCreated++;
      if ((i + 1) % 5 === 0) {
        console.log(`✓ Created ${i + 1}/${customerCount} customers...`);
      }
    } else {
      console.error(`✗ Failed to create customer ${i}: ${response.status}`);
    }
  }

  console.log(`\n=== Data Preparation Summary ===`);
  console.log(`✓ Customer Types: ${createdTypes.length}/5`);
  console.log(`✓ Customer Statuses: ${createdStatuses.length}/5`);
  console.log(`✓ Customers: ${customersCreated}/${customerCount}`);
  console.log(`================================\n`);

  // Store created IDs in shared array for potential cleanup
  return {
    types: createdTypes.map(t => t['@id']),
    statuses: createdStatuses.map(s => s['@id']),
    customersCount: customersCreated,
  };
}

export function teardown(data) {
  console.log('\nTest data preparation completed.');
  console.log('Note: Data persists for load testing. Use CleanupCustomers.js to remove test data.');
}
