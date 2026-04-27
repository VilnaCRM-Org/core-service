import http from 'k6/http';
import { check, sleep } from 'k6';
import { Counter, Rate, Trend } from 'k6/metrics';
import counter from 'k6/x/counter';
import InsertCustomersUtils from '../../utils/insertCustomersUtils.js';
import ScenarioUtils from '../../utils/scenarioUtils.js';
import Utils from '../../utils/utils.js';

const scenarioName = 'cacheReadWriteRace';
const WRITE_EVERY_N_ITERATIONS = 5;
const EVENTUAL_CONSISTENCY_GRACE_MS = 750;

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const insertCustomersUtils = new InsertCustomersUtils(utils, scenarioName);
const customers = insertCustomersUtils.loadInsertedCustomers();

const readDuration = new Trend('cache_race_read_duration', true);
const writeDuration = new Trend('cache_race_write_duration', true);
const staleAfterUpdate = new Rate('cache_race_stale_after_update');
const successfulReads = new Counter('cache_race_successful_reads');
const successfulWrites = new Counter('cache_race_successful_writes');

export function setup() {
  const warmupCount = Math.min(customers.length, 100);

  for (let i = 0; i < warmupCount; i++) {
    const customer = customers[i];
    http.get(`${utils.getBaseHttpUrl()}/${customer.id}`, utils.getJsonHeader());
  }

  if (warmupCount === 0) {
    throw new Error('cacheReadWriteRace needs prepared customers.');
  }

  return {
    customers: customers.slice(0, warmupCount),
  };
}

export const options = {
  ...scenarioUtils.getOptions(),
  thresholds: {
    ...scenarioUtils.getOptions().thresholds,
    http_req_failed: ['rate<0.01'],
    cache_race_stale_after_update: ['rate<0.05'],
    cache_race_read_duration: ['p(95)<300', 'p(99)<600'],
    cache_race_write_duration: ['p(95)<800', 'p(99)<1200'],
  },
};

export default function cacheReadWriteRace(data) {
  const iteration = counter.up();
  const index = iteration % data.customers.length;
  const customer = data.customers[index];
  utils.checkCustomerIsDefined(customer);

  if (iteration % WRITE_EVERY_N_ITERATIONS !== 0) {
    readCustomer(customer.id);
    return;
  }

  updateThenReadCustomer(customer.id);
}

function readCustomer(id) {
  const response = http.get(`${utils.getBaseHttpUrl()}/${id}`, utils.getJsonHeader());
  readDuration.add(response.timings.duration);

  const success = check(response, {
    'race read status is 200': res => res.status === 200,
    'race read has data': res => res.body && res.body.length > 0,
  });

  if (success) {
    successfulReads.add(1);
  }
}

function updateThenReadCustomer(id) {
  const nextCustomer = utils.generateCustomer();
  const payload = JSON.stringify({
    initials: nextCustomer.initials,
    email: nextCustomer.email,
    phone: nextCustomer.phone,
  });

  const updateResponse = http.patch(
    `${utils.getBaseHttpUrl()}/${id}`,
    payload,
    utils.getMergePatchHeader()
  );
  writeDuration.add(updateResponse.timings.duration);

  const updateSucceeded = check(updateResponse, {
    'race update status is 200': res => res.status === 200,
  });

  if (!updateSucceeded) {
    return;
  }

  successfulWrites.add(1);
  sleep(EVENTUAL_CONSISTENCY_GRACE_MS / 1000);

  const readResponse = http.get(`${utils.getBaseHttpUrl()}/${id}`, utils.getJsonHeader());
  readDuration.add(readResponse.timings.duration);

  const bodyMatchesUpdate =
    readResponse.status === 200 &&
    readResponse.body.includes(nextCustomer.initials) &&
    readResponse.body.includes(nextCustomer.email);

  staleAfterUpdate.add(!bodyMatchesUpdate);
}

export function handleSummary(data) {
  const staleRateValue = data.metrics.cache_race_stale_after_update?.values?.rate;
  const readP95Value = data.metrics.cache_race_read_duration?.values?.['p(95)'];
  const writeP95Value = data.metrics.cache_race_write_duration?.values?.['p(95)'];
  const successfulReadValue = data.metrics.cache_race_successful_reads?.values?.count;
  const successfulWriteValue = data.metrics.cache_race_successful_writes?.values?.count;
  const staleRate = typeof staleRateValue === 'number' ? (staleRateValue * 100).toFixed(2) : '0.00';
  const readP95 = typeof readP95Value === 'number' ? readP95Value.toFixed(2) : 'N/A';
  const writeP95 = typeof writeP95Value === 'number' ? writeP95Value.toFixed(2) : 'N/A';
  const successfulReadCount = typeof successfulReadValue === 'number' ? successfulReadValue : 0;
  const successfulWriteCount = typeof successfulWriteValue === 'number' ? successfulWriteValue : 0;

  console.log('\n=== CACHE READ/WRITE RACE SUMMARY ===');
  console.log(`Successful Reads: ${successfulReadCount}`);
  console.log(`Successful Writes: ${successfulWriteCount}`);
  console.log(`Stale After Update Rate: ${staleRate}%`);
  console.log(`Read P95: ${readP95}ms`);
  console.log(`Write P95: ${writeP95}ms`);
  console.log('======================================\n');

  return {
    stdout: [
      '',
      '=== CACHE READ/WRITE RACE SUMMARY ===',
      `Successful Reads: ${successfulReadCount}`,
      `Successful Writes: ${successfulWriteCount}`,
      `Stale After Update Rate: ${staleRate}%`,
      `Read P95: ${readP95}ms`,
      `Write P95: ${writeP95}ms`,
      '======================================',
      '',
    ].join('\n'),
  };
}
