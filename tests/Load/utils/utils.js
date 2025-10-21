import { check } from 'k6';
import http from 'k6/http';

export default class Utils {
  constructor() {
    const config = this.getConfig();
    const host = config.apiHost;
    const port = config.apiPort;

    this.baseUrl = `http://${host}:${port}/api`;
    this.baseHttpUrl = this.baseUrl;
  }

  getConfig() {
    try {
      return JSON.parse(open('../config.json'));
    } catch (error) {
      try {
        return JSON.parse(open('../config.json.dist'));
      } catch (error) {
        console.error('Failed to load configuration from config.json and config.json.dist:', error);
      }
    }
  }

  getBaseHttpUrl() {
    return this.baseHttpUrl;
  }

  getCLIVariable(variable) {
    return `${__ENV[variable]}`;
  }

  checkResponse(response, checkName, checkFunction) {
    check(response, { [checkName]: res => checkFunction(res) });
  }

  getJsonHeader() {
    return {
      headers: {
        'Content-Type': 'application/ld+json',
      },
    };
  }

  getMergePatchHeader() {
    return {
      headers: {
        'Content-Type': 'application/merge-patch+json',
      },
    };
  }

  createCustomer(customerData) {
    const payload = JSON.stringify(customerData);
    return http.post(`${this.baseHttpUrl}/customers`, payload, this.getJsonHeader());
  }

  createCustomerType(typeData) {
    const payload = JSON.stringify(typeData);
    return http.post(`${this.baseHttpUrl}/customer_types`, payload, this.getJsonHeader());
  }

  createCustomerStatus(statusData) {
    const payload = JSON.stringify(statusData);
    return http.post(`${this.baseHttpUrl}/customer_statuses`, payload, this.getJsonHeader());
  }
}
