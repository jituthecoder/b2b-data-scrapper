# Central Control Plane Architecture Specification

## 1. Overview & System Mission
This platform serves as an enterprise-grade B2B business intelligence and website data platform designed for **5+ million domains** and **100+ million related records**.

The Laravel application is the **CENTRAL CONTROL PLANE** of the entire ecosystem.

### Core Architecture Rules
1. **Single Source of Truth**: Laravel handles all business logic, database writes, data validation, normalization, job management, APIs, authentication, and crawler orchestration.
2. **Crawler Isolation**: Crawlers are separate, stateless Python/Node.js microservices. Crawlers **NEVER** have direct PostgreSQL credentials.
3. **API-Driven Communication**: Stateless crawlers communicate with Laravel solely via secure, authenticated HTTP REST APIs.
4. **Horizontal Scalability**: Distributed workers can claim jobs with lease locks (`lease_expires_at`) without sequence locking or race conditions.
5. **PostgreSQL + S3 Storage Separation**: Structural entity data is saved in PostgreSQL; large blobs (HTML snapshots, raw crawler payloads) are pushed to AWS S3.
6. **5M Streamed Ingestion Engine**: Domain imports run via low-memory line-by-line generators (`fopen` + `fgetcsv`) and dispatch 1,000-record queue jobs (`ImportDomainChunkJob`).
7. **External Provider Isolation**: Integrations (Google PageSpeed Insights, Email Verification) use dedicated client adapters in `app/Domain/Integrations/`.
8. **Web Admin Dashboard UI**: Premium dark glassmorphism web interface at `/admin` with pages for:
   - Dashboard Overview (`/admin`)
   - Domain Explorer (`/admin/domains` + `POST /admin/domains` custom domain registration form)
   - Detailed Domain Intelligence & Execution Timeline (`/admin/domains/{id}`)
   - Crawler Workers Monitor (`/admin/crawlers`)
   - Crawl Job Queue (`/admin/jobs`)
   - Google API Keys Manager (`/admin/google-keys`)
9. **Multi-Stage Stepped Crawl Pipeline**:
   - **Stage 1 (Priority 100)**: Fast Reachability Check (`reachability`). Halts pipeline immediately if site is dead/unresponsive.
   - **Stage 2 (Priority 50)**: Full Homepage Scrape (`homepage`). Extracts company, tech stack, emails, phones, social profiles, and **WordPress Theme & Plugin Ecosystem**.
   - **Stage 3 (Priority 25)**: Deep Sub-Page Scrapes (`subpage`). Discovered Contact, About, Careers links.
10. **WordPress Theme & Plugin Detection Engine**:
   - Microservice extractor (`wordpressExtractor.js`): Analyzes `/wp-content/themes/*` and `/wp-content/plugins/*` paths to identify active themes and installed plugins.
   - Database Categorization: Categorizes as `WordPress Theme` and `WordPress Plugin` in `technologies` and `domain_technologies`.
   - Web Admin UI Display: Dedicated **WordPress Ecosystem** card on `/admin/domains/{id}` showcasing active themes (e.g. `Understrap`) and installed plugins (e.g. `Yoast SEO`, `WooCommerce`, `WPBakery`, `GDPR Cookie Compliance`).
11. **Manual Immediate Crawl Trigger**:
   - Admin button on `/admin/domains` and `/admin/domains/{id}` (`POST /admin/domains/{id}/crawl`) queues Stage 1 job with **Top Priority (`priority = 1000`)**.
12. **Database-Driven Google Search API Key Pool Engine**:
   - Web UI (`/admin/google-keys`): Manage 1,000s of free API keys (add, edit, delete, toggle active, reset quotas, bulk import textarea).
   - Migration `google_api_keys` & Model `GoogleApiKey`.
   - Artisan Commands: `php artisan google:add-keys` & `php artisan google:reset-quotas`.
   - Least-Recently-Used Rotation: `GoogleSearchKeyPoolService` load-balances requests across all active keys (`last_used_at ASC`) and enforces safe 95 daily limit.
13. **Standalone Crawler Microservice**: Located in `c:\xampp\htdocs\data-scrapper\b2b-crawler-service`. Independent Node.js microservice deployable on multiple laptops and cloud servers (EC2 PM2).
