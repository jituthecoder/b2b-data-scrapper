const axios = require('axios');
const os = require('os');
require('dotenv').config();

class ControlPlaneClient {
  constructor() {
    this.baseUrl = process.env.LARAVEL_API_URL || 'http://127.0.0.1:8000/api/v1/crawler';
    this.hostname = process.env.WORKER_HOSTNAME || os.hostname();
    this.concurrency = parseInt(process.env.WORKER_CONCURRENCY || '20', 10);
    this.capabilities = (process.env.WORKER_CAPABILITIES || 'reachability,homepage,tech_detect,contact_discover,social,seo').split(',');
    
    this.crawlerId = process.env.CRAWLER_ID || null;
    this.apiKey = process.env.CRAWLER_KEY || null;
  }

  getHeaders() {
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-Crawler-ID': this.crawlerId,
      'X-Crawler-Key': this.apiKey,
    };
  }

  async registerNode() {
    console.log(`[Worker] Connecting to Laravel Control Plane at ${this.baseUrl}...`);
    try {
      const payload = {
        hostname: this.hostname,
        version: '1.0.0',
        worker_count: this.concurrency,
        capabilities: this.capabilities,
      };

      if (this.crawlerId && this.apiKey) {
        payload.crawler_id = this.crawlerId;
        payload.api_key = this.apiKey;
      }

      const response = await axios.post(`${this.baseUrl}/register`, payload);

      this.crawlerId = response.data.crawler_id;
      this.apiKey = response.data.api_key;

      console.log(`[Worker Registered Successfully] ID: ${this.crawlerId}`);
      return true;
    } catch (error) {
      console.error(`[Worker Registration Failed] ${error.response ? JSON.stringify(error.response.data) : error.message}`);
      return false;
    }
  }

  async sendHeartbeat() {
    if (!this.crawlerId) return false;
    try {
      await axios.post(`${this.baseUrl}/heartbeat`, {}, { headers: this.getHeaders() });
      return true;
    } catch (error) {
      console.error(`[Heartbeat Failed] ${error.message}`);
      return false;
    }
  }

  async claimJobs(limit = 20) {
    if (!this.crawlerId) return [];
    try {
      const response = await axios.post(
        `${this.baseUrl}/jobs/claim`,
        { limit },
        { headers: this.getHeaders() }
      );
      return response.data.jobs || [];
    } catch (error) {
      console.error(`[Job Claim Error] ${error.response ? JSON.stringify(error.response.data) : error.message}`);
      return [];
    }
  }

  async submitResult(jobId, payload) {
    if (!this.crawlerId) return false;
    try {
      await axios.post(
        `${this.baseUrl}/jobs/${jobId}/result`,
        payload,
        { headers: this.getHeaders() }
      );
      console.log(`[Result Submitted] Job: ${jobId}`);
      return true;
    } catch (error) {
      console.error(`[Result Submission Error] Job ${jobId}: ${error.message}`);
      return false;
    }
  }

  async reportFailure(jobId, errorMsg) {
    if (!this.crawlerId) return false;
    try {
      await axios.post(
        `${this.baseUrl}/jobs/${jobId}/failed`,
        { error: errorMsg, response_code: 500 },
        { headers: this.getHeaders() }
      );
      console.log(`[Failure Reported] Job: ${jobId}`);
      return true;
    } catch (error) {
      console.error(`[Failure Report Error] Job ${jobId}: ${error.message}`);
      return false;
    }
  }
}

module.exports = ControlPlaneClient;
