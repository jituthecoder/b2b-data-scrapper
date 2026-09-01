# Local Development & Testing Instructions

## 1. Environment Requirements
- **PHP**: 8.2 or 8.3 with `pdo_pgsql`, `pgsql`, `mbstring`, `curl`, `json` extensions enabled.
- **Composer**: 2.x
- **PostgreSQL**: 15+ (AWS RDS in production / staging)
- **Redis**: 7+ for queue processing and cache
- **AWS S3**: Bucket configured for HTML snapshot and raw JSON object storage

---

## 2. Setting Up Environment
```bash
# Copy environment template
copy .env.example .env

# Generate application key
php artisan key:generate

# Execute database migrations and seeders
php artisan migrate --seed
```

---

## 3. Bulk CSV Domain Import Command
See full guide in [`README.md`](file:///c:/xampp/htdocs/data-scrapper/b2b-data-scarpper/README.md).

- **Local Sample Testing**:
  ```bash
  php artisan import:domains sample_domains.csv --sync
  ```
- **Production 5M Domain Dataset**:
  ```bash
  php artisan import:domains /path/to/5m_domains.csv
  ```

---

## 4. Running Automated Tests
The project uses PHPUnit for automated unit and integration testing.

```bash
# Run the complete test suite
php artisan test
```
Testing runs against SQLite in-memory DB by default (`phpunit.xml`) to allow rapid sub-second test execution.
