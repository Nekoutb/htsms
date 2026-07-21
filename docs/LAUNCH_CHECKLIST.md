# HTSMS launch checklist

This checklist distinguishes the completed engineering MVP from the external gates required before selling HTSMS as a production service at `htsms.cm-ea.com`.

## Engineering acceptance completed

- [x] Multi-tenant Laravel API and customer portal
- [x] Registration, email verification, password reset, roles, API keys, and platform-admin email MFA
- [x] Android phone pairing, SIM inventory, heartbeat, outbound leasing, delivery status, and inbound SMS relay
- [x] Contacts, consent records, suppressions, campaigns, quotas, scheduling, retries, and inbound opt-out handling
- [x] Signed webhook delivery with retry and replay support
- [x] Subscription plans and manual subscription-change approval workflow
- [x] Production Docker Compose topology for web, queue workers, scheduler, PostgreSQL, Redis, Nginx, and Caddy TLS
- [x] Proprietary licence, deployment, operations, API, Android, security, and clean-room documentation
- [x] Backend test suite, PHPStan, Pint, frontend production build, and dependency audits
- [x] Android unit tests, lint, debug APK, and optimized unsigned release APK
- [x] Private GitHub repository, CI workflow, and container release workflow

## Required before a public production launch

### Business and compliance

- [ ] Obtain legal approval for the Terms of Service, Privacy Policy, Data Processing Agreement, Acceptable Use Policy, retention policy, marketing-consent language, and abuse response process.
- [ ] Confirm Cameroon and each served market's telecom, privacy, consumer-protection, sender-identification, and marketing-message requirements with qualified counsel and the relevant carriers/regulators.
- [ ] Choose a payment provider and provide production credentials. The MVP deliberately uses a manual invoice/subscription-approval workflow; automated charging is not yet integrated.
- [ ] Define final plan names, prices, currencies, tax handling, message/device limits, refund policy, trial rules, and suspension rules.

### Production infrastructure

- [ ] Provision a supported Linux host or container platform and provide deployment access.
- [ ] Point DNS for `htsms.cm-ea.com` to the production host and verify HTTPS issuance and renewal.
- [ ] Generate production application, database, Redis, SMTP, webhook, monitoring, and backup secrets; do not reuse development values.
- [ ] Configure a transactional email provider and validate SPF, DKIM, and DMARC for the sending domain.
- [ ] Configure encrypted off-site backups and complete a documented database restore drill.
- [ ] Configure uptime, queue-depth, error-rate, storage, certificate-expiry, and device-offline alerts.
- [ ] Run migrations, readiness checks, queue/scheduler checks, and a send/receive smoke test after deployment.

### Android distribution and carrier validation

- [ ] Create and securely escrow the production Android signing key, configure CI signing secrets, and publish the signed APK checksum.
- [ ] Test pairing, dual-SIM selection, multipart SMS, inbound SMS, delivery receipts, reboot recovery, battery optimization, intermittent data, and credential revocation on physical devices.
- [ ] Complete a device matrix covering representative Samsung, Tecno, Infinix, and Xiaomi devices and supported Android versions.
- [ ] Complete a carrier matrix for MTN Cameroon and Orange Cameroon, then repeat for every additional supported market/carrier.
- [ ] Document customer installation, permissions, background-operation, SIM-cost, and troubleshooting guidance using production screenshots.

### Security and operational acceptance

- [ ] Commission an independent penetration test covering tenant isolation, authentication, APIs, webhook SSRF, queues, the Android client, and production infrastructure; remediate all critical/high findings.
- [ ] Conduct an abuse simulation for spam reports, stolen API keys, compromised phones, SIM removal, unexpected cost spikes, opt-outs, account suspension, and data deletion.
- [ ] Verify production rate limits and quotas under load, including campaign fanout and webhook retry backlogs.
- [ ] Resolve the GitHub-hosted Actions `startup_failure` condition so CI and release jobs actually receive a runner.
- [ ] Build and publish the production container from a passing trusted CI run, then verify its provenance/SBOM artifacts.
- [ ] Train support/operations staff and establish escalation ownership and incident-response contacts.

### Pilot and launch decision

- [ ] Run an internal pilot using real phones and non-customer test numbers.
- [ ] Run a consented closed customer pilot with explicit sending limits and daily review.
- [ ] Record customer acceptance, reconcile billing, review carrier complaints and delivery rates, and obtain a written go-live decision.
- [ ] Enable public signup/charging only after every applicable item above is signed off.

## Acceptance commands

From `backend`:

```bash
php artisan test
vendor/bin/phpstan analyse
vendor/bin/pint --test
npm run build
composer audit
npm audit
```

From `android`, with JDK 17 and Android SDK API 35 installed:

```bash
./gradlew testDebugUnitTest assembleDebug lintDebug assembleRelease
```

From the repository root, after creating a production `.env` outside version control:

```bash
docker compose --env-file .env -f deploy/compose.production.yml config
docker compose --env-file .env -f deploy/compose.production.yml up -d
```

The software repository can be merged and versioned independently of these external gates. A public launch is not complete until the applicable unchecked items have evidence and an accountable approver.
