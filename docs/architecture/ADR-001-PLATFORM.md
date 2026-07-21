# ADR-001: HTSMS Platform Architecture

**Status:** Accepted baseline  
**Date:** 21 July 2026

## Decision

HTSMS will use:

- Laravel 12 on PHP 8.3+ for the API, business workflows, administration, billing, scheduled work, and queue orchestration.
- PostgreSQL as the production system of record.
- Redis for queues, leases, distributed locks, idempotency coordination, and rate limiting.
- Nuxt 3/Vue 3 for the customer and platform administration applications.
- Native Kotlin and Jetpack Compose for the Android gateway.
- Firebase Cloud Messaging only as a wake-up signal; plaintext message content is not included in push payloads.
- S3-compatible encrypted object storage for controlled imports/exports and evidence files.
- OpenAPI 3.1 as the public HTTP contract.

## Architectural boundaries

- `Identity`: users, sessions, MFA, organizations, memberships, and roles.
- `Commercial`: plans, subscriptions, usage, entitlements, invoices, and payment events.
- `Gateway`: devices, pairing, credentials, SIM slots, capabilities, heartbeats, and commands.
- `Messaging`: messages, attempts, leases, state transitions, scheduling, delivery events, and conversations.
- `Integration`: API keys, webhooks, signatures, retries, and public API resources.
- `Marketing`: contacts, consent, suppressions, templates, audiences, campaigns, and campaign recipients.
- `Trust`: business verification, policy rules, anomaly signals, reviews, suspensions, and audit events.

Controllers validate and authorize requests, then delegate to typed application services. State transitions are enforced by domain rules. Long-running or retryable work runs in explicit queues. External services are accessed through interfaces so tests never require live Firebase, carrier, payment, email, or webhook systems.

## Queue classes

1. `transactional` — highest priority, short jobs, strict latency objective.
2. `device-events` — heartbeat and phone acknowledgements.
3. `webhooks` — outbound customer callbacks.
4. `marketing` — rate-controlled campaign expansion and dispatch.
5. `imports-exports` — file processing and report generation.
6. `notifications` — customer email and operational notices.

## Security consequences

- Every tenant-owned table includes `organization_id` and appropriate compound indexes.
- Route binding alone is not considered authorization; policies/services must enforce tenant ownership.
- API keys and device credentials are stored as hashes, with non-secret prefixes for identification.
- Device commands use leases and idempotent acknowledgements.
- Sensitive queued jobs are encrypted where their payload cannot be reduced to opaque identifiers.
- Webhook destinations are checked against SSRF policy and signed with rotating secrets.
- Routine logs exclude message content, complete phone numbers, credentials, and consent evidence.

## Rejected alternatives

- Forking httpSMS: rejected because AGPL network-source obligations conflict with the proprietary requirement.
- Cross-platform Android framework: rejected because reliable SMS, SIM, broadcast, and background behaviour require native platform control.
- Synchronous phone dispatch during an API request: rejected because phone connectivity is nondeterministic.
- Marketing and transactional traffic in one queue: rejected because campaign volume could delay critical messages.

