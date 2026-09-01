# B2B Platform Intelligence & Website Scraper Platform

Enterprise-grade B2B business intelligence control plane built with Laravel 11, PostgreSQL, and distributed Node.js crawler microservices designed for **5+ million domains**.

---

## Production Quick Start Guide

### 1. Execute Streamed 5M Domain Importer
Run the low-memory CSV domain importer to load target domains into PostgreSQL:

```bash
php artisan import:domains sample_domains.csv --sync
```

---

## 2. Standalone Node.js Crawler Microservice

Located in `c:\xampp\htdocs\data-scrapper\b2b-crawler-service`.

### Installation & Execution
```bash
cd c:\xampp\htdocs\data-scrapper\b2b-crawler-service
npm install
npm start
```

---

## 3. Web Admin Dashboard UI (`http://127.0.0.1:8000/admin`)

Access the visual Control Plane monitoring dashboard in your browser:
- **Dashboard Overview (`/admin`)**: Active vs Stopped worker counts, stage breakdown metrics.
- **Domain Explorer (`/admin/domains`)**: View, search, and click **🚀 Crawl Now** on any domain.
- **Domain Intelligence & Audit Log (`/admin/domains/{id}`)**: 360° view of extracted companies, tech stacks, emails, social profiles, and step-by-step stage execution audit trail.
- **Crawler Workers Monitor (`/admin/crawlers`)**: Real-time worker node health indicators (🟢 Active vs 🔴 Stopped).
- **Crawl Job Queue Inspector (`/admin/jobs`)**: Monitor pending, claimed, and completed crawl jobs.
- **Google API Keys Manager (`/admin/google-keys`)**: Manage 1,000s of free Google Custom Search API keys with daily limit rotation and bulk import.

---

## 4. Multi-Stage Stepped Crawling Pipeline

```text
[ POST /api/v1/domains ]  ({ "domain": "stripe.com" })
          │
          ▼
┌────────────────────────────────────────────────────────┐
│ STAGE 1: Reachability Check (Priority: 100, Fast)      │
│ Fast HTTP status check (200 OK vs 404/500/timeout)     │
└─────────────────────────┬──────────────────────────────┘
                          │
            Is Accessible?│
            ┌─────────────┴─────────────┐
            │ YES                       │ NO
            ▼                           ▼
┌───────────────────────────┐ ┌────────────────────────────────────┐
│ STAGE 2: Deep Extraction  │ │ Stop Further Crawling               │
│ (Priority: 50)            │ │ Mark domain is_accessible = false   │
│ - Company Name & Meta     │ │ Mark domain crawl_status = failed  │
│ - Social Profiles         │ └────────────────────────────────────┘
│ - Tech Stack (BuiltWith)  │
│ - Emails & Phones         │
└───────────┬───────────────┘
            │
            ▼
┌────────────────────────────────────────────────────────┐
│ Google Search Fallback Engine (If Contact URL Missing) │
│ Queries "site:domain inurl:contact" via Key Pool       │
└───────────┬────────────────────────────────────────────┘
            │
            ▼
┌───────────────────────────┐
│ STAGE 3: Sub-Page Scrapes │
│ Contact, About, Careers   │
└───────────────────────────┘
```

---

## 5. AWS EC2 Hosting & Production PM2 Deployment Guide

You can host both the **Laravel Control Plane** and **Node.js Crawler Microservice** on a single AWS EC2 instance (e.g., `t3.medium` or `c6i.large`) using PM2 process manager.

### Step 1: Install Node.js & PM2 on AWS EC2
```bash
# Install Node.js 20 LTS
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs

# Install PM2 globally
sudo npm install -g pm2
```

### Step 2: Configure Crawler Microservice on EC2
```bash
cd /var/www/b2b-crawler-service
npm install

# Create production .env
cat <<EOT > .env
CONTROL_PLANE_URL=http://localhost/api/v1/crawler
CRAWLER_KEY=crawler_sec_prod_9988776655443322
CRAWLER_NAME=EC2-Worker-Node-1
CONCURRENCY=20
POLL_INTERVAL_MS=5000
EOT
```

### Step 3: Launch Crawler Worker Background Processes via PM2
```bash
# Start crawler worker under PM2 supervision
pm2 start src/worker.js --name "b2b-crawler-1"

# Save PM2 state for automatic EC2 reboot survival
pm2 save
pm2 startup
```

### Step 4: Monitor Worker Health in PM2
```bash
pm2 status
pm2 logs b2b-crawler-1
```

---

## 6. Running Test Suite

```bash
php artisan test
```
