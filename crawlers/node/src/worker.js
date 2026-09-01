const axios = require('axios');
const pLimit = require('p-limit');
const ControlPlaneClient = require('./client');
const extractTechnologies = require('./extractors/technologyExtractor');
const extractWordPressDetails = require('./extractors/wordpressExtractor');
const extractEmails = require('./extractors/emailExtractor');
const extractPhones = require('./extractors/phoneExtractor');
const extractSocialProfiles = require('./extractors/socialExtractor');
const extractSeoMetadata = require('./extractors/seoExtractor');
const extractDiscoveredPages = require('./extractors/pageExtractor');
const extractCompanyLogo = require('./extractors/logoExtractor');
require('dotenv').config();

class DistributedCrawlerWorker {
  constructor() {
    this.client = new ControlPlaneClient();
    this.concurrency = this.client.concurrency || 20;
    this.limit = pLimit(this.concurrency);
    this.pollInterval = parseInt(process.env.POLL_INTERVAL_MS || '5000', 10);
    this.isRunning = false;
  }

  async start() {
    console.log(`====================================================`);
    console.log(` B2B Intelligence Standalone Node.js Crawler Worker `);
    console.log(`====================================================`);
    console.log(`Worker Concurrency: ${this.concurrency} concurrent requests`);

    const registered = await this.client.registerNode();
    if (!registered) {
      console.error(`[Fatal] Could not register with Laravel Control Plane. Retrying in 10s...`);
      setTimeout(() => this.start(), 10000);
      return;
    }

    // Schedule 30-second heartbeat
    setInterval(() => {
      this.client.sendHeartbeat();
    }, 30000);

    this.isRunning = true;
    this.runLoop();
  }

  async runLoop() {
    while (this.isRunning) {
      try {
        console.log(`[Worker] Polling Laravel Control Plane for unique unclaimed jobs (Batch: ${this.concurrency})...`);
        const jobs = await this.client.claimJobs(this.concurrency);

        if (!jobs || jobs.length === 0) {
          console.log(`[Queue Empty] No pending jobs matching worker capabilities. Sleeping ${this.pollInterval / 1000}s...`);
          await this.sleep(this.pollInterval);
          continue;
        }

        console.log(`[Jobs Claimed] Successfully locked ${jobs.length} unique domain jobs.`);

        // Process claimed jobs concurrently using p-limit pool
        const tasks = jobs.map(job => this.limit(() => this.processJob(job)));
        await Promise.all(tasks);

      } catch (error) {
        console.error(`[Loop Error] ${error.message}`);
        await this.sleep(this.pollInterval);
      }
    }
  }

  async processJob(job) {
    const domainName = job.domain;
    if (!domainName) {
      console.warn(`[Skip] Job ${job.job_id} missing domain string.`);
      return;
    }

    const targetUrl = domainName.startsWith('http') ? domainName : `https://${domainName}`;
    console.log(`[Crawling] Domain: ${domainName} (${targetUrl})...`);

    const startTime = Date.now();
    try {
      const response = await axios.get(targetUrl, {
        timeout: 15000,
        maxRedirects: 5,
        headers: {
          'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
          'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
          'Accept-Language': 'en-US,en;q=0.9',
          'Cache-Control': 'no-cache',
        },
        validateStatus: false,
      });

      const html = response.data && typeof response.data === 'string' ? response.data : '';
      const durationMs = Date.now() - startTime;

      const seo = extractSeoMetadata(html, targetUrl);
      const baseTech = extractTechnologies(html, response.headers);
      const wpTech = extractWordPressDetails(html);
      const technologies = [...baseTech, ...wpTech];

      const emails = extractEmails(html);
      const phones = extractPhones(html);
      const socialProfiles = extractSocialProfiles(html);
      const pages = extractDiscoveredPages(html, targetUrl);
      const logoUrl = extractCompanyLogo(html, targetUrl);

      const isAccessible = (response.status >= 200 && response.status < 500) && response.status !== 404;

      const payload = {
        domain_status: {
          is_accessible: isAccessible,
          http_status: response.status,
          final_url: response.request ? response.request.res.responseUrl || targetUrl : targetUrl,
          canonical_url: seo.canonical,
        },
        seo: seo,
        company: {
          name: seo.title || domainName,
          description: seo.description,
          logo_url: logoUrl,
        },
        technologies: technologies,
        emails: emails,
        phones: phones,
        social_profiles: socialProfiles,
        pages: pages,
        duration_ms: durationMs,
        response_code: response.status,
      };

      await this.client.submitResult(job.job_id, payload);
    } catch (error) {
      const durationMs = Date.now() - startTime;
      console.error(`[Crawl Failed] ${domainName}: ${error.message}`);
      await this.client.reportFailure(job.job_id, error.message);
    }
  }

  sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
  }
}

// Instantiate and start worker
if (require.main === module) {
  const worker = new DistributedCrawlerWorker();
  worker.start();
}

module.exports = DistributedCrawlerWorker;
