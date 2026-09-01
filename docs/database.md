# PostgreSQL Database Architecture & Schema Specification

## 1. Primary Key & Storage Guidelines
- Core high-volume tables (`domains`, `companies`, `contacts`, `emails`, `pages`, `technologies`, `domain_technologies`) utilize 64-bit integer auto-increment (`id()`) for max B-Tree index density, low storage overhead, and optimal join execution in PostgreSQL.
- Distributed queue tables (`crawl_jobs`) use `UUID` string primary keys to prevent ID enumeration and sequence lock contention across concurrent worker nodes.
- Large text objects (raw HTML snapshots and crawler response payloads) are stored in **AWS S3**, saving PostgreSQL buffer cache space for fast indexing.

---

## 2. Table Schemas & Key Indexes

### `domains`
- **Primary Key**: `id` (bigint)
- **Unique Indexes**: `domain`, `normalized_domain`
- **Query Indexes**: `tld`, `status`, `last_crawled_at`, `next_crawl_at`, `crawl_status`, `priority`
- **Fields**: `domain`, `normalized_domain`, `scheme`, `www_variant`, `tld`, `status`, `is_accessible`, `http_status`, `final_url`, `canonical_url`, `first_discovered_at`, `last_crawled_at`, `next_crawl_at`, `crawl_status`, `crawl_attempts`, `last_crawl_error`, `priority`, `timestamps`, `softDeletes`.

### `companies`
- **Primary Key**: `id` (bigint)
- **Query Indexes**: `name`, `normalized_name`, `industry`, `country`
- **Fields**: `name`, `normalized_name`, `description`, `industry`, `employee_count_range`, `founded_year`, `country`, `state_region`, `city`, `address`, `postal_code`, `metadata` (jsonb), `confidence_score`, `timestamps`, `softDeletes`.

### `company_domains` (Pivot)
- **Primary Key**: `id` (bigint)
- **Unique Composite Constraint**: `(company_id, domain_id)`
- **Foreign Keys**: `company_id` -> `companies(id)` (cascade), `domain_id` -> `domains(id)` (cascade)
- **Fields**: `company_id`, `domain_id`, `is_primary`, `timestamps`.

### `contacts`
- **Primary Key**: `id` (bigint)
- **Query Indexes**: `company_id`, `full_name`, `job_title`
- **Foreign Key**: `company_id` -> `companies(id)` (nullOnDelete)
- **Fields**: `company_id`, `first_name`, `last_name`, `full_name`, `job_title`, `department`, `seniority`, `confidence_score`, `timestamps`, `softDeletes`.

### `emails`
- **Primary Key**: `id` (bigint)
- **Unique Indexes**: `email`, `normalized_email`
- **Foreign Key**: `domain_id` -> `domains(id)` (nullOnDelete)
- **Fields**: `email`, `normalized_email`, `domain_id`, `type`, `verification_status`, `confidence_score`, `first_discovered_at`, `last_checked_at`, `timestamps`, `softDeletes`.

### `contact_emails` (Pivot)
- **Primary Key**: `id` (bigint)
- **Unique Composite Constraint**: `(contact_id, email_id)`
- **Foreign Keys**: `contact_id` -> `contacts(id)` (cascade), `email_id` -> `emails(id)` (cascade)
- **Fields**: `contact_id`, `email_id`, `is_primary`, `timestamps`.

### `phones`
- **Primary Key**: `id` (bigint)
- **Query Index**: `normalized_phone`
- **Fields**: `phone_number`, `normalized_phone`, `country_code`, `type`, `confidence_score`, `timestamps`.

### `contact_phones` (Pivot)
- **Primary Key**: `id` (bigint)
- **Unique Composite Constraint**: `(contact_id, phone_id)`
- **Foreign Keys**: `contact_id` -> `contacts(id)` (cascade), `phone_id` -> `phones(id)` (cascade)
- **Fields**: `contact_id`, `phone_id`, `timestamps`.

### `social_profiles`
- **Primary Key**: `id` (bigint)
- **Unique Index**: `normalized_url`
- **Query Indexes**: `platform`, `(entity_type, entity_id)`
- **Fields**: `platform`, `profile_url`, `normalized_url`, `username_handle`, `entity_type`, `entity_id`, `timestamps`.

### `technologies`
- **Primary Key**: `id` (bigint)
- **Unique Indexes**: `name`, `slug`
- **Query Index**: `category`
- **Fields**: `name`, `slug`, `category`, `icon_url`, `description`, `timestamps`.

### `domain_technologies` (Pivot)
- **Primary Key**: `id` (bigint)
- **Unique Composite Constraint**: `(domain_id, technology_id)`
- **Foreign Keys**: `domain_id` -> `domains(id)` (cascade), `technology_id` -> `technologies(id)` (cascade)
- **Fields**: `domain_id`, `technology_id`, `version`, `detection_source`, `confidence_score`, `first_detected_at`, `last_detected_at`, `timestamps`.

### `pages`
- **Primary Key**: `id` (bigint)
- **Unique Composite Index**: `(domain_id, normalized_url)`
- **Query Indexes**: `domain_id`, `page_type`, `normalized_url`
- **Foreign Key**: `domain_id` -> `domains(id)` (cascade)
- **Fields**: `domain_id`, `url`, `normalized_url`, `page_type`, `http_status`, `title`, `html_snapshot_s3_path`, `content_metadata` (jsonb), `crawled_at`, `timestamps`.

### `data_sources`
- **Primary Key**: `id` (bigint)
- **Unique Index**: `name`
- **Fields**: `name`, `type`, `description`, `is_active`, `timestamps`.

### `crawl_jobs`
- **Primary Key**: `id` (UUID)
- **Unique Index**: `idempotency_key`
- **Query Indexes**: `domain_id`, `status`, `job_type`, `crawler_id`, `lease_expires_at`
- **Foreign Key**: `domain_id` -> `domains(id)` (cascade)
- **Fields**: `id`, `domain_id`, `job_type`, `priority`, `status`, `crawler_id`, `claimed_at`, `lease_expires_at`, `completed_at`, `failed_at`, `attempt_count`, `max_attempts`, `last_error`, `payload` (jsonb), `raw_result_s3_path`, `idempotency_key`, `timestamps`.

### `crawl_attempts`
- **Primary Key**: `id` (bigint)
- **Query Indexes**: `crawl_job_id`, `crawler_id`
- **Foreign Key**: `crawl_job_id` -> `crawl_jobs(id)` (cascade)
- **Fields**: `id`, `crawl_job_id`, `crawler_id`, `attempt_number`, `status`, `duration_ms`, `response_code`, `error_message`, `created_at`.
