import http from 'k6/http';
import { check } from 'k6';
import { Trend, Rate, Counter } from 'k6/metrics';
import counter from 'k6/x/counter';
import ScenarioUtils from '../../utils/scenarioUtils.js';
import Utils from '../../utils/utils.js';
import InsertCustomersUtils from '../../utils/insertCustomersUtils.js';

const scenarioName = 'cachePerformance';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const insertCustomersUtils = new InsertCustomersUtils(utils, scenarioName);

const customers = insertCustomersUtils.loadInsertedCustomers();

// Custom metrics for cache performance analysis
const requestDuration = new Trend('cache_request_duration', true);
const successRate = new Rate('cache_success_rate');
const totalRequests = new Counter('cache_total_requests');
const fastResponseRate = new Rate('cache_fast_response_rate'); // < 100ms suggests cache hit

// Threshold for "fast" response (likely cache hit)
// Note: This is heuristic-based. Real cache verification is done in integration tests.
const FAST_RESPONSE_THRESHOLD_MS = 100;

export function setup() {
  // Warm up phase: access each customer once to populate cache
  console.log('Cache warmup: populating cache with initial reads...');

  const warmupCount = Math.min(customers.length, 100);
  let warmupSuccesses = 0;

  for (let i = 0; i < warmupCount; i++) {
    const customer = customers[i];
    const response = http.get(`${utils.getBaseHttpUrl()}/${customer.id}`, utils.getJsonHeader());

    if (response.status === 200 && response.body && response.body.length > 0) {
      warmupSuccesses++;
    }
  }

  console.log(`Cache warmup complete. ${warmupSuccesses}/${warmupCount} customers cached.`);

  if (warmupSuccesses === 0) {
    throw new Error('Cache warmup failed - no successful responses. Check if API is running.');
  }

  return {
    customers: customers,
    warmupCount: warmupCount,
  };
}

export const options = scenarioUtils.getOptions();
options.thresholds = {
  ...options.thresholds,
  http_req_failed: ['rate<0.01'],
  cache_success_rate: ['rate>0.99'],
  cache_fast_response_rate: ['rate>0.80'],
  cache_request_duration: ['p(95)<200', 'p(99)<400'],
};

export default function cachePerformance(data) {
  const customerIndex = counter.up() % data.warmupCount;
  const customer = data.customers[customerIndex];
  utils.checkCustomerIsDefined(customer);

  const { id } = customer;

  const response = http.get(`${utils.getBaseHttpUrl()}/${id}`, utils.getJsonHeader());

  // Record metrics
  totalRequests.add(1);
  requestDuration.add(response.timings.duration);

  const isSuccess = check(response, {
    'status is 200': r => r.status === 200,
    'response has data': r => r.body && r.body.length > 0,
  });

  successRate.add(isSuccess ? 1 : 0);
  fastResponseRate.add(isSuccess && response.timings.duration < FAST_RESPONSE_THRESHOLD_MS);
}

export function handleSummary(data) {
  const total = data.metrics.cache_total_requests
    ? data.metrics.cache_total_requests.values.count
    : 0;
  const fastRatio = data.metrics.cache_fast_response_rate
    ? (data.metrics.cache_fast_response_rate.values.rate * 100).toFixed(2)
    : '0.00';

  const avgDuration = data.metrics.cache_request_duration
    ? data.metrics.cache_request_duration.values.avg.toFixed(2)
    : 'N/A';
  const p95Duration = data.metrics.cache_request_duration
    ? data.metrics.cache_request_duration.values['p(95)'].toFixed(2)
    : 'N/A';
  const p99Duration = data.metrics.cache_request_duration
    ? data.metrics.cache_request_duration.values['p(99)'].toFixed(2)
    : 'N/A';

  const successRateValue = data.metrics.cache_success_rate
    ? (data.metrics.cache_success_rate.values.rate * 100).toFixed(2)
    : 'N/A';

  console.log('\n=== CACHE PERFORMANCE LOAD TEST SUMMARY ===');
  console.log(`Total Requests: ${total}`);
  console.log(`Success Rate: ${successRateValue}%`);
  console.log(`Fast Response Rate (<${FAST_RESPONSE_THRESHOLD_MS}ms): ${fastRatio}%`);
  console.log(`Avg Duration: ${avgDuration}ms`);
  console.log(`P95 Duration: ${p95Duration}ms`);
  console.log(`P99 Duration: ${p99Duration}ms`);
  console.log('============================================');
  console.log('Note: Cache correctness is verified by integration tests.');
  console.log('This load test measures performance under concurrent load.\n');

  const httpFailRate = data.metrics.http_req_failed ? data.metrics.http_req_failed.values.rate : 0;
  if (httpFailRate > 0.01) {
    // More than 1% failure rate
    console.error(
      `FAIL: HTTP error rate (${(httpFailRate * 100).toFixed(2)}%) exceeds 1% threshold`
    );
    return { stdout: JSON.stringify({ failed: true, reason: 'High error rate' }) };
  }

  return {};
}
