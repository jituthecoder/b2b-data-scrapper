# Distributed Crawler API Architecture & Interface Specification

## 1. Authentication & Security Policy
All crawler instances (Python, Node.js, Go) communicate with Laravel strictly via HTTP APIs over TLS.

- **Authentication Protocol**: Hashed API key authentication passed via `X-Crawler-ID` and `X-Crawler-Key` headers, enforced by `AuthenticateCrawler` middleware.
- **Worker Identity**: Every request validates worker active status and registered capabilities in `crawler_nodes`.
- **Database Access Restriction**: Crawler nodes have zero database access credentials.

---

## 2. Distributed Control Plane Flow

```
+------------------+                    +-----------------------+
| Python / Node.js | --- Claim Job ---> |   Laravel API         |
| Crawler Node     |                    | (Control Plane)       |
|                  | <-- Job Payload -- |                       |
|                  |                    |                       |
|                  | -- Post Result --> | Validates & Normalizes|
|                  |                    | Saves to Postgres/S3  |
+------------------+                    +-----------------------+
```

---

## 3. Endpoints Implemented

### Worker Registration
- `POST /api/v1/crawler/register`
- Registers a crawler worker instance (`hostname`, `version`, `worker_count`, `capabilities`). Returns `crawler_id` and raw `api_key`.

### Heartbeat
- `POST /api/v1/crawler/heartbeat`
- Periodic check-in by worker node updating `last_heartbeat_at`.

### Claim Jobs
- `POST /api/v1/crawler/jobs/claim`
- Claims pending jobs matching worker capabilities. Sets 10-minute lease timestamp (`lease_expires_at`).

### Submit Job Result
- `POST /api/v1/crawler/jobs/{job_id}/result`
- Submits structured extraction output. Saves raw payload to S3/filesystem storage, returns `202 Accepted`, and dispatches async `ProcessCrawlResultJob`.
- **Idempotency Guarantee**: If a job was already completed, returns `200 OK` without creating duplicate records.

### Mark Job Failed
- `POST /api/v1/crawler/jobs/{job_id}/failed`
- Reports execution failure, increments attempt counter, and schedules retries or marks job `failed`.

---

## 4. Lease Timeout Maintenance
- Command: `php artisan crawler:release-expired-leases`
- Automatically releases claimed jobs with expired leases (`lease_expires_at < now()`) back to `pending`.
