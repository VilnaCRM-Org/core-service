import fs from 'node:fs';
import path from 'node:path';

const rootDir = process.cwd();
const resultsRoot = path.resolve(
  rootDir,
  process.env.BENCHMARK_RESULTS_ROOT ?? 'tests/Load/results/runtime-comparison'
);
const outputPath = path.resolve(
  rootDir,
  process.env.BENCHMARK_REPORT_OUTPUT ?? 'docs/frankenphp-worker-mode-comparison.md'
);
const catalogPath = path.resolve(rootDir, 'tests/Load/benchmark-scenarios.json');

const modeConfigs = [
  { key: 'php-fpm', label: 'php-fpm' },
  { key: 'frankenphp-no-worker', label: 'FrankenPHP without worker mode' },
  { key: 'frankenphp-worker', label: 'FrankenPHP with worker mode' },
];

const catalog = JSON.parse(fs.readFileSync(catalogPath, 'utf8'));

function readJson(filePath) {
  return JSON.parse(fs.readFileSync(filePath, 'utf8'));
}

function formatNumber(value, fractionDigits = 2) {
  return Number(value).toFixed(fractionDigits);
}

function formatMs(value) {
  return `${formatNumber(value)} ms`;
}

function formatRps(value) {
  return `${formatNumber(value)} RPS`;
}

function formatRatio(value) {
  if (value === null) {
    return 'n/a';
  }

  return `${formatNumber(value, 1)}x`;
}

function formatPercent(value) {
  if (value === null) {
    return 'n/a';
  }

  return `${formatNumber(value, 1)}%`;
}

function formatRate(value) {
  if (value === null) {
    return 'n/a';
  }

  return formatPercent(value * 100);
}

function formatCompactMetrics(metrics) {
  return `${formatNumber(metrics.rps)} / ${formatNumber(metrics.avg)} / ${formatNumber(
    metrics.p95
  )} / ${formatNumber(metrics.p99)}`;
}

function escapeTableCell(value) {
  return `${value}`.replaceAll('|', '\\|');
}

function improvementPercent(baseline, current) {
  if (baseline === 0) {
    return null;
  }

  return ((baseline - current) / baseline) * 100;
}

function deltaPercent(baseline, current) {
  if (baseline === 0) {
    return null;
  }

  return ((current - baseline) / baseline) * 100;
}

function describeRpsComparison(current, baseline) {
  if (baseline === 0) {
    return 'n/a';
  }

  const ratio = current / baseline;

  if (Math.abs(ratio - 1) < 0.05) {
    return 'roughly the same RPS';
  }

  if (ratio > 1) {
    return `${formatRatio(ratio)} more RPS`;
  }

  return `${formatPercent((1 - ratio) * 100)} lower RPS`;
}

function describeLatencyComparison(current, baseline, label = 'avg latency') {
  const change = deltaPercent(baseline, current);

  if (change === null) {
    return `n/a ${label}`;
  }

  if (Math.abs(change) < 5) {
    return `roughly the same ${label}`;
  }

  if (change < 0) {
    return `${formatPercent(Math.abs(change))} lower ${label}`;
  }

  return `${formatPercent(change)} higher ${label}`;
}

function extractScenarioMetrics(summary, durationSeconds) {
  const durationMetric = summary.metrics['http_req_duration{test_type:benchmark}'];
  const requestsMetric = summary.metrics['http_reqs{test_type:benchmark}'];
  const checksMetric = summary.metrics['checks{scenario:benchmark}'];

  if (!durationMetric || !requestsMetric) {
    throw new Error('Benchmark summary is missing benchmark-tagged metrics.');
  }

  return {
    requestCount: requestsMetric.count,
    rps: requestsMetric.count / durationSeconds,
    avg: durationMetric.avg,
    p95: durationMetric['p(95)'],
    p99: durationMetric['p(99)'],
    checksRate: checksMetric?.value ?? null,
  };
}

const modeMetadata = new Map();
for (const mode of modeConfigs) {
  const metadataPath = path.join(resultsRoot, mode.key, 'metadata.json');
  if (!fs.existsSync(metadataPath)) {
    throw new Error(`Missing benchmark metadata for mode "${mode.key}" at ${metadataPath}`);
  }

  modeMetadata.set(mode.key, readJson(metadataPath));
}

const durationSeconds = modeMetadata.get('frankenphp-worker').durationSeconds;
const warmupDurationSeconds = modeMetadata.get('frankenphp-worker').warmupDurationSeconds;
const vus = modeMetadata.get('frankenphp-worker').vus;

const scenarioResults = catalog.map(entry => {
  const slug = entry.id.replaceAll('/', '__');
  const modes = {};

  for (const mode of modeConfigs) {
    const summaryPath = path.join(resultsRoot, mode.key, `${slug}.summary.json`);
    if (!fs.existsSync(summaryPath)) {
      throw new Error(`Missing summary for ${entry.id} in mode "${mode.key}" at ${summaryPath}`);
    }

    modes[mode.key] = extractScenarioMetrics(readJson(summaryPath), durationSeconds);
  }

  return { ...entry, modes };
});

const workerBestRpsCount = scenarioResults.filter(result => {
  const workerRps = result.modes['frankenphp-worker'].rps;
  return modeConfigs.every(mode => workerRps >= result.modes[mode.key].rps);
}).length;

const workerBestAvgCount = scenarioResults.filter(result => {
  const workerAvg = result.modes['frankenphp-worker'].avg;
  return modeConfigs.every(mode => workerAvg <= result.modes[mode.key].avg);
}).length;

const cleanAllModesCount = scenarioResults.filter(result =>
  modeConfigs.every(mode => {
    const checksRate = result.modes[mode.key].checksRate;
    return checksRate === null || checksRate >= 0.99;
  })
).length;

const workerLowCheckCount = scenarioResults.filter(result => {
  const checksRate = result.modes['frankenphp-worker'].checksRate;
  return checksRate !== null && checksRate < 0.99;
}).length;

const strongestPhpFpmWins = [...scenarioResults]
  .map(result => ({
    label: result.label,
    ratio:
      result.modes['php-fpm'].rps === 0
        ? null
        : result.modes['frankenphp-worker'].rps / result.modes['php-fpm'].rps,
    latencyImprovement: improvementPercent(
      result.modes['php-fpm'].avg,
      result.modes['frankenphp-worker'].avg
    ),
  }))
  .filter(result => result.ratio !== null)
  .sort((left, right) => right.ratio - left.ratio)
  .slice(0, 5);

const strongestNoWorkerWins = [...scenarioResults]
  .map(result => ({
    label: result.label,
    ratio:
      result.modes['frankenphp-no-worker'].rps === 0
        ? null
        : result.modes['frankenphp-worker'].rps / result.modes['frankenphp-no-worker'].rps,
    latencyImprovement: improvementPercent(
      result.modes['frankenphp-no-worker'].avg,
      result.modes['frankenphp-worker'].avg
    ),
  }))
  .filter(result => result.ratio !== null)
  .sort((left, right) => right.ratio - left.ratio)
  .slice(0, 5);

function buildEndpointNotes(result) {
  const phpFpm = result.modes['php-fpm'];
  const noWorker = result.modes['frankenphp-no-worker'];
  const worker = result.modes['frankenphp-worker'];
  const notes = [];

  const reliabilityNotes = modeConfigs
    .map(mode => {
      const metrics = result.modes[mode.key];

      if (metrics.checksRate === null || metrics.checksRate >= 0.99) {
        return null;
      }

      return `${mode.label} check rate ${formatRate(metrics.checksRate)}`;
    })
    .filter(Boolean);

  if (reliabilityNotes.length > 0) {
    notes.push(...reliabilityNotes);
  }

  if (
    worker.rps < phpFpm.rps &&
    worker.rps < noWorker.rps &&
    worker.avg > phpFpm.avg &&
    worker.avg > noWorker.avg
  ) {
    notes.push('Worker slower than both baselines');
  }

  return notes.join('; ');
}

const sections = Array.from(new Set(catalog.map(entry => entry.section))).map(section => ({
  name: section,
  items: scenarioResults.filter(result => result.section === section),
}));

const lines = [];
lines.push('# FrankenPHP Worker Mode Runtime Comparison');
lines.push('');
lines.push(
  `Local fixed-VU k6 benchmark. Every endpoint was warmed first, then measured with the same settings in every runtime mode: ${vus} VUs for ${durationSeconds}s after a ${warmupDurationSeconds}s warmup.`
);
lines.push('');
lines.push('## Scope');
lines.push('');
lines.push(
  'This report compares php-fpm, FrankenPHP without worker mode, and FrankenPHP with worker mode for the full committed endpoint inventory used by the benchmark harness.'
);
lines.push('');
lines.push('## Headline Results');
lines.push('');
lines.push(
  `FrankenPHP worker mode had the highest throughput on ${workerBestRpsCount}/${scenarioResults.length} benchmarked endpoints and the lowest average latency on ${workerBestAvgCount}/${scenarioResults.length}.`
);
lines.push('');
lines.push(
  `All three runtime modes maintained a >=99% check rate on ${cleanAllModesCount}/${scenarioResults.length} endpoints. FrankenPHP worker mode dropped below that threshold on ${workerLowCheckCount}/${scenarioResults.length} endpoints.`
);
lines.push('');
lines.push('The biggest worker-mode throughput wins over php-fpm were:');
lines.push('');
for (const result of strongestPhpFpmWins) {
  lines.push(
    `- ${result.label}: ${formatRatio(result.ratio)} more RPS, average latency down ${formatPercent(result.latencyImprovement)}.`
  );
}
lines.push('');
lines.push('The biggest worker-mode throughput wins over FrankenPHP without worker mode were:');
lines.push('');
for (const result of strongestNoWorkerWins) {
  lines.push(
    `- ${result.label}: ${formatRatio(result.ratio)} more RPS, average latency down ${formatPercent(result.latencyImprovement)}.`
  );
}

for (const section of sections) {
  lines.push('');
  lines.push(`## ${section.name}`);
  lines.push('');
  lines.push('Cell format: `RPS / avg ms / p95 ms / p99 ms`.');
  lines.push('');
  lines.push('| Endpoint | php-fpm | FrankenPHP no worker | FrankenPHP worker | Notes |');
  lines.push('|---|---:|---:|---:|---|');
  for (const result of section.items) {
    lines.push(
      `| ${escapeTableCell(result.label)} | ${formatCompactMetrics(result.modes['php-fpm'])} | ${formatCompactMetrics(result.modes['frankenphp-no-worker'])} | ${formatCompactMetrics(result.modes['frankenphp-worker'])} | ${escapeTableCell(buildEndpointNotes(result))} |`
    );
  }
}

lines.push('');
lines.push('## Notes');
lines.push('');
lines.push(
  '- The benchmark runner uses benchmark-tagged k6 submetrics so setup and teardown requests do not distort the reported request rate or latency for the measured endpoint body.'
);
lines.push(
  '- Destructive delete scenarios size their fixtures from a warmup pass before the measured run so the 30-second benchmark does not run out of entities partway through.'
);
lines.push(
  '- Numbers are local-machine results and will move with CPU limits, Docker scheduling, and background host load.'
);

fs.writeFileSync(outputPath, `${lines.join('\n')}\n`);
console.log(`Wrote ${path.relative(rootDir, outputPath)}`);
