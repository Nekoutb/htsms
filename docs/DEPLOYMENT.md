# HTSMS production deployment

This runbook deploys the proprietary HTSMS stack at `https://htsms.cm-ea.com` using Docker Compose, PostgreSQL, Redis, PHP-FPM, dedicated workers, Nginx, and Caddy-managed TLS.

## Prerequisites

- Linux host with Docker Engine 27+ and Compose v2
- DNS `A`/`AAAA` records for `htsms.cm-ea.com` pointing to the host
- Inbound TCP 80/443 and UDP 443 permitted
- SMTP credentials, private container registry access, and off-host encrypted backups
- A release image built from a reviewed commit

## First deployment

1. Copy `deploy/.env.production.example` to `deploy/.env.production` on the host.
2. Generate `APP_KEY` with `php artisan key:generate --show` in a trusted environment.
3. Generate independent random PostgreSQL and Redis passwords of at least 32 characters.
4. Populate SMTP settings and set `HTSMS_IMAGE` to an immutable image digest or release tag.
5. Restrict the environment file to the deployment account (`chmod 600`).
6. Authenticate the host to the private registry, pull, and run `docker compose --env-file .env.production -f compose.production.yml up -d`.
7. Confirm `curl --fail https://htsms.cm-ea.com/health/ready` returns `{"status":"ready"}`.
8. Execute registration, verification, workspace, pairing, API-key, test-message, inbound, and webhook smoke tests.

The application service runs migrations before serving. Queue and scheduler services wait for PostgreSQL and Redis health.

## Release, rollback, and recovery

Before release, back up PostgreSQL and record the current image digest. Rehearse migrations in staging, then deploy the immutable image. Roll back to the previous digest. If a schema change is not backward-compatible, restore the pre-release backup into an isolated instance first; never run destructive rollback commands blindly in production.

Create nightly custom-format `pg_dump` backups, encrypt them before off-host transfer, and retain according to the approved data policy. Quarterly, restore into an isolated PostgreSQL instance, compare critical row counts, and run the acceptance smoke test. Record measured RPO/RTO.

## Production gates

- `APP_DEBUG=false`; HTTPS/HSTS and security headers verified
- PostgreSQL/Redis not exposed publicly
- Named administrators use verified email and MFA before public launch
- Alerts cover queue age, offline devices, webhook failures, database capacity, backups, and certificate expiry
- Logs are centralized, access-controlled, and free of secrets/ordinary message content
- Release APK is signed outside the repository with checksum and certificate fingerprint published

Do not enable external registration until legal, carrier, payment, device-matrix, recovery, and penetration-test gates are signed off.
