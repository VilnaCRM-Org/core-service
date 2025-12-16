import http from 'k6/http';
import { Counter, Trend, Rate } from 'k6/metrics';
import counter from 'k6/x/counter';
import ScenarioUtils from '../../utils/scenarioUtils.js';
import Utils from '../../utils/utils.js';
import InsertCustomersUtils from '../../utils/insertCustomersUtils.js';

const scenarioName = 'cachePerformance';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const insertCustomersUtils = new InsertCustomersUtils(utils, scenarioName);

const customers = insertCustomersUtils.loadInsertedCustomers();

// Custom metrics for cache performance
const cacheHits = new Counter('cache_hits');
const cacheMisses = new Counter('cache_misses');
const cacheHitRate = new Rate('cache_hit_rate');
const cacheHitDuration = new Trend('cache_hit_duration', true);
const cacheMissDuration = new Trend('cache_miss_duration', true);

// Track which customers have been accessed (for hit/miss detection)
const accessedCustomers = new Set();

export function setup() {
  // Warm up phase: access each customer once to populate cache
  console.log('Cache warmup: populating cache with initial reads...');

  const warmupCount = Math.min(customers.length, 100);
  for (let i = 0; i < warmupCount; i++) {
    const customer = customers[i];
    const response = http.get(
      `${utils.getBaseHttpUrl()}/${customer.id}`,
      utils.getJsonHeader()
    );

    if (response.status === 200) {
      console.log(`Warmed up customer ${i + 1}/${warmupCount}`);
    }
  }

  console.log(`Cache warmup complete. ${warmupCount} customers cached.`);

  return {
    customers: customers,
    warmupCount: warmupCount,
  };
}

export const options = scenarioUtils.getOptions();

export default function cachePerformance(data) {
  const customerIndex = counter.up() % data.warmupCount;
  const customer = data.customers[customerIndex];
  utils.checkCustomerIsDefined(customer);

  const { id } = customer;
  const startTime = Date.now();

  const response = http.get(`${utils.getBaseHttpUrl()}/${id}`, utils.getJsonHeader());

  const duration = Date.now() - startTime;

  // Check response
  const isSuccess = utils.checkResponse(response, 'is status 200', res => res.status === 200);

  if (isSuccess) {
    // After warmup, all requests should be cache hits
    // We detect this by response time - cache hits are typically <50ms
    const isCacheHit = duration < 50;

    if (isCacheHit) {
      cacheHits.add(1);
      cacheHitRate.add(1);
      cacheHitDuration.add(duration);
    } else {
      cacheMisses.add(1);
      cacheHitRate.add(0);
      cacheMissDuration.add(duration);
    }
  }
}

export function handleSummary(data) {
  const hitCount = data.metrics.cache_hits ? data.metrics.cache_hits.values.count : 0;
  const missCount = data.metrics.cache_misses ? data.metrics.cache_misses.values.count : 0;
  const totalRequests = hitCount + missCount;
  const hitRatio = totalRequests > 0 ? (hitCount / totalRequests * 100).toFixed(2) : 0;

  const avgHitDuration = data.metrics.cache_hit_duration
    ? data.metrics.cache_hit_duration.values.avg.toFixed(2)
    : 'N/A';
  const avgMissDuration = data.metrics.cache_miss_duration
    ? data.metrics.cache_miss_duration.values.avg.toFixed(2)
    : 'N/A';

  console.log('\n=== CACHE PERFORMANCE SUMMARY ===');
  console.log(`Total Requests: ${totalRequests}`);
  console.log(`Cache Hits: ${hitCount}`);
  console.log(`Cache Misses: ${missCount}`);
  console.log(`Cache Hit Ratio: ${hitRatio}%`);
  console.log(`Avg Cache Hit Duration: ${avgHitDuration}ms`);
  console.log(`Avg Cache Miss Duration: ${avgMissDuration}ms`);
  console.log('================================\n');

  // Fail if cache hit ratio is below 80% after warmup
  if (totalRequests > 10 && parseFloat(hitRatio) < 80) {
    console.error(`FAIL: Cache hit ratio (${hitRatio}%) is below 80% threshold`);
    return { 'stdout': JSON.stringify({ failed: true, reason: 'Low cache hit ratio' }) };
  }

  return {};
}
