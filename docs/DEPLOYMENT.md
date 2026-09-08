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

For a new self-hosted installation, `bootstrap-production-env.sh` can create the file with generated application, PostgreSQL, and Redis secrets. It refuses to overwrite a non-empty environment:

```bash
cd deploy
HTSMS_BOOTSTRAP_IMAGE=htsms:release-tag ./bootstrap-production-env.sh
```

The bootstrap defaults email to the local log transport. Replace it with approved SMTP settings and validate delivery before allowing external registration.

## Deployment behind an existing host Caddy

Use this mode when the host already has Caddy or another HTTPS reverse proxy on ports 80 and 443. The override publishes HTSMS Nginx only on the host loopback interface and prevents the bundled Caddy service from starting:

```bash
docker compose \
  --env-file .env.production \
  -f compose.production.yml \
  -f compose.external-caddy.yml \
  up -d
```

The default local endpoint is `127.0.0.1:8085`. It may be changed with `HTSMS_HTTP_PORT`, but it must remain bound to `127.0.0.1` rather than a public interface. Configure the host Caddy with:

```caddy
htsms.cm-ea.com {
    reverse_proxy 127.0.0.1:8085
    encode zstd gzip
    header {
        -Server
    }
}
```

Before reloading Caddy, validate the complete host configuration. Keep the Cloudflare DNS record in DNS-only mode until Caddy has obtained a valid certificate and both the HTTPS homepage and `/health/ready` work directly. Then select Cloudflare Full (strict) and enable the proxy.

For a host using system Nginx and Certbot instead of Caddy, install `nginx-host.conf.example` as a site, validate and reload Nginx, and then request the certificate:

```bash
sudo cp nginx-host.conf.example /etc/nginx/sites-available/htsms
sudo ln -s /etc/nginx/sites-available/htsms /etc/nginx/sites-enabled/htsms
sudo nginx -t
sudo systemctl reload nginx
sudo certbot --nginx --non-interactive --redirect -d htsms.cm-ea.com
```

The application service runs migrations before serving. Queue and scheduler services wait for PostgreSQL and Redis health.

Merges to `main` publish `ghcr.io/nekoutb/htsms:sha-<full-commit-sha>` and `latest`; version tags such as `v1.0.0` also publish that version. Production should pin the immutable SHA tag after its release checks pass.

## Staging environment

Staging runs at `https://dev.htsms.cm-ea.com` as a second Compose stack on the
production host. Isolation comes from the Compose project name `htsms-staging`,
which namespaces every container, network, and volume, so staging has its own
PostgreSQL and Redis and cannot reach production's data.

Because the two stacks share a machine, staging is not a capacity test: a
runaway staging job competes with live traffic for CPU, memory, and disk.

### Ports

Production's bundled Caddy owns host ports 80 and 443, so staging must never
publish them. Each stack binds Nginx to the loopback interface only, and the
host reverse proxy terminates TLS for both hostnames:

| Environment | Hostname | Loopback port |
| --- | --- | --- |
| production | `htsms.cm-ea.com` | `127.0.0.1:8085` |
| staging | `dev.htsms.cm-ea.com` | `127.0.0.1:8086` |

Install `Caddyfile.host.example` as the host Caddy configuration to serve both.

### First staging deployment

1. Add a DNS record for `dev.htsms.cm-ea.com` pointing at the host. Keep it in
   Cloudflare DNS-only mode until Caddy has issued a certificate.
2. Copy `deploy/.env.staging.example` to `deploy/.env.staging` and `chmod 600`.
3. Generate an `APP_KEY` and PostgreSQL and Redis passwords that are all
   independent of production's. Staging shares a host with the live service, so
   a reused credential turns a staging compromise into a production one.
4. Leave `MAIL_MAILER=log` unless a sending domain and recipient allowlist have
   been set up specifically for staging. Staging must not email real customers.
5. Pin `HTSMS_IMAGE` to an immutable `sha-<full-commit-sha>` tag.
6. Start the stack:

```bash
cd deploy
docker compose \
  --env-file .env.staging \
  -f compose.production.yml \
  -f compose.staging.yml \
  up -d
```

7. Confirm the environment reports itself correctly:

```bash
curl --fail https://dev.htsms.cm-ea.com/health/ready
```

### Promoting a change to staging

```bash
cd deploy
HTSMS_IMAGE=ghcr.io/nekoutb/htsms:sha-<full-commit-sha> \
docker compose \
  --env-file .env.staging \
  -f compose.production.yml \
  -f compose.staging.yml \
  up -d
```

Every push to `main` publishes `sha-<full-commit-sha>`. To stage a branch before
merging, run the `HTSMS release image` workflow on that branch first.

### Reading back what is deployed

`/health/ready` reports the exact commit each environment is serving, baked into
the image at build time via the `HTSMS_COMMIT` build argument. To see every
environment at once:

```bash
./scripts/environment-status.sh
```

The `Environment status` workflow runs the same probe on a schedule and records
the result against the GitHub environments, so the deployment history reflects
what is actually serving rather than what a deploy log claimed.

## Release, rollback, and recovery

Before release, back up PostgreSQL and record the current image digest. Rehearse migrations in staging, then deploy the immutable image. Roll back to the previous digest. If a schema change is not backward-compatible, restore the pre-release backup into an isolated instance first; never run destructive rollback commands blindly in production.

Create nightly custom-format `pg_dump` backups, encrypt them before off-host transfer, and retain according to the approved data policy. Quarterly, restore into an isolated PostgreSQL instance, compare critical row counts, and run the acceptance smoke test. Record measured RPO/RTO.

## Production gates

- `APP_DEBUG=false`; HTTPS/HSTS and security headers verified
- PostgreSQL/Redis not exposed publicly
- Named administrators use verified email and strong passwords before public launch
- Alerts cover queue age, offline devices, webhook failures, database capacity, backups, and certificate expiry
- Logs are centralized, access-controlled, and free of secrets/ordinary message content
- Release APK is signed outside the repository with checksum and certificate fingerprint published

Do not enable external registration until legal, carrier, payment, device-matrix, recovery, and penetration-test gates are signed off.
