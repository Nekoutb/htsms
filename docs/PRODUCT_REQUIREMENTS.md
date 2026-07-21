# HTSMS Product Requirements — Launch Baseline

**Version:** 0.1  
**Date:** 21 July 2026  
**Status:** Working baseline

## Product outcome

HTSMS enables an outside customer to subscribe, pair one or more Android phones, and send or receive SMS through the customer's own SIM using a dashboard or authenticated HTTP API. It supports high-priority transactional traffic and permission-based marketing campaigns without allowing campaigns to starve transactional messages.

## Launch personas

- **Organization owner:** controls subscription, security, users, and data.
- **Administrator:** manages phones, API keys, webhooks, and sending limits.
- **Developer:** integrates the API and diagnoses webhook/message events.
- **Campaign manager:** manages consented contacts, templates, and campaigns.
- **Support agent:** assists a customer using audited, least-privilege tools.
- **Platform administrator:** verifies businesses, investigates abuse, suspends risk, and manages service health.

## Required transactional capabilities

- Account registration, verification, recovery, MFA-ready security, and organizations.
- User invitations and organization roles.
- Secure, expiring QR/manual phone pairing.
- Single- and dual-SIM device capability reporting.
- Device heartbeat, online status, app version, battery, connectivity, and SIM state.
- Scoped API keys that can be rotated and revoked.
- Individual and scheduled SMS submission.
- E.164 number normalization and validation.
- Idempotent submission and recipient-safe retry behaviour.
- Configurable per-device and per-SIM throughput.
- Offline queueing, leases, expiration, and recovery.
- Inbound SMS synchronization without importing historical unrelated messages.
- Sent, delivered, failed, and expired states with normalized failure reasons.
- Signed webhooks with retries, logs, replay protection, and manual replay.
- Searchable message timeline and conversation view.
- English and French interfaces and system communications.

## Required marketing capabilities

- Contacts, lists, tags, custom fields, CSV import, preview, and rejected-row report.
- Consent purpose, source, method, timestamp, evidence, and withdrawal history.
- Global and list-level suppression.
- STOP and ARRET handling plus policy-approved variants.
- Templates and variables with preview and SMS-part estimation.
- Audience selection, test send, approval, schedule, pause, resume, and cancel.
- Quiet hours, duplicate-recipient prevention, and fair campaign throttling.
- Separate transactional and marketing queues and quotas.
- Campaign delivery/failure/expiration/opt-out reporting and export.
- Progressive limits, business verification, anomaly detection, and emergency suspension.

## Explicit launch exclusions

- iOS as an SMS gateway.
- WhatsApp, RCS, email, voice, or social messaging.
- Carrier-provided virtual numbers or SMS credits.
- Short codes and alphanumeric sender IDs.
- MMS unless separately approved after SMS reliability is proven.
- Automated purchase or circumvention of carrier bundles.
- Contact scraping or purchased-list enrichment.

## Service objectives for beta validation

- Accepted API requests are never silently lost.
- Duplicate API calls with the same idempotency key do not produce duplicate messages.
- A lost or duplicate push notification does not produce a duplicate carrier send.
- Transactional queue dispatch begins within 10 seconds while a paired phone is online under normal load.
- Webhook delivery is attempted within 10 seconds after a recorded event under normal load.
- Tenant data cannot be accessed by a user, API key, phone, or administrator outside authorized scope.
- A 1,000-message controlled queue completes without message loss or duplicate carrier sends.

## Business rules requiring owner approval

- Subscription tiers, currencies, prices, trials, refunds, and taxes.
- Per-plan device, user, contact, storage, API, and message-part limits.
- Default message-content retention and customer-configurable range.
- Supported destination countries at launch.
- Customer verification evidence and prohibited industries/content.
- Maximum campaign rates and quiet-hour policy.
- Payment provider and hosting/data region.

