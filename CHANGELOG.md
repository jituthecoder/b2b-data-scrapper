# Changelog

All notable changes to this project will be documented in this file.

## [WordPress Theme & Plugin Detection Engine] - 2026-09-01

### Added
- **WordPress Theme & Plugin Extractor (`wordpressExtractor.js`)**:
  - Automatically detects active **WordPress Themes** from `/wp-content/themes/*` paths.
  - Automatically detects all active **WordPress Plugins** from `/wp-content/plugins/*` paths (e.g. `Yoast SEO`, `WooCommerce`, `WPBakery Page Builder`, `GDPR Cookie Compliance`, `Elementor`, `Contact Form 7`, `W3 Speedster`).
  - Saves theme and plugin technology records into PostgreSQL (`technologies` & `domain_technologies`).
- **Web Admin Dashboard UI Extension (`/admin/domains/{id}`)**:
  - Added dedicated **WordPress Ecosystem** card highlighting installed themes and plugins.
- **Automated Test Suite Expansion**: Added `WordPressDetectionTest` bringing total test suite to **52 passing tests (234 assertions)**.

## [Web Admin Custom Domain Import & Queueing] - 2026-09-01
- Custom Domain Import Feature (`POST /admin/domains`) with line-by-line bulk paste and priority 1000 crawl queueing.

## [Manual Immediate Crawl Trigger & AWS EC2 PM2 Deployment] - 2026-09-01
- Manual Crawl Trigger Action (`POST /admin/domains/{id}/crawl`) and AWS EC2 PM2 Production Deployment Guide in `README.md`.

## [Detailed Domain Intelligence & Stage Execution Timeline] - 2026-09-01
- Domain Detail Intelligence Page (`/admin/domains/{id}`) featuring 360° overview banner, stage task audit trail, and extracted entity tabs.

## [Web Admin Google API Key Management Dashboard] - 2026-09-01
- Web Admin Google API Key Management Page (`/admin/google-keys`) for managing 1,000s of free API keys.

## [Database-Driven Google Search API Key Pool Engine] - 2026-09-01
- Database schema `google_api_keys`, `GoogleApiKey` model, `php artisan google:add-keys` bulk importer command, `php artisan google:reset-quotas` midnight reset command, and least-recently-used load-balancer.

## [Multi-Stage Pipeline & Real-Time Worker Observability] - 2026-09-01
- Multi-Stage Stepped Crawling Pipeline (Stage 1 Reachability -> Stage 2 Homepage -> Stage 3 Sub-pages), `POST /api/v1/domains` endpoint, and Real-Time Worker Health Dashboard (Active vs Stopped).

## [Phase 5 Standalone Node.js Crawler Microservice] - 2026-08-31
- Independent repository (`c:\xampp\htdocs\data-scrapper\b2b-crawler-service`) with zero database access credentials, 20-worker request pool (`p-limit`), and BuiltWith-style technology fingerprinting.

## [Phase 1 Foundation] - 2026-08-31
- Initial release of Laravel Central Control Plane and PostgreSQL core database schema.
