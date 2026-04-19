export default class ThresholdsBuilder {
  constructor() {
    this.thresholds = {};
    this.skipDurationThresholds = ['1', 'true', 'yes'].includes(
      `${__ENV.K6_SKIP_DURATION_THRESHOLDS ?? ''}`.toLowerCase()
    );
  }

  addThreshold(scenarioName, config) {
    this.thresholds[`http_req_duration{test_type:${scenarioName}}`] = [
      this.skipDurationThresholds ? 'max>=0' : 'p(99)<' + config.threshold,
    ];
    this.thresholds[`http_reqs{test_type:${scenarioName}}`] = ['count>=0'];
    this.thresholds[`checks{scenario:${scenarioName}}`] = ['rate>0.99'];
    return this;
  }

  build() {
    return this.thresholds;
  }
}
