# HTSMS Detailed Project Timeline

**Project:** HTSMS proprietary Android SMS gateway SaaS  
**Primary domain:** `htsms.cm-ea.com`  
**Planning baseline:** 21 July 2026  
**Execution start:** 27 July 2026  
**Controlled beta target:** 18 October 2026  
**Commercial launch target:** 13 December 2026  
**Post-launch stabilization ends:** 10 January 2027

## 1. Approved product decisions

- HTSMS will be a subscription SaaS offered to outside customers.
- Customers connect their own Android phones and SIM cards.
- HTSMS supports transactional and permission-based marketing SMS.
- The entire implementation must remain proprietary.
- HTSMS will be developed as a clean-room implementation. No AGPL httpSMS source code will be copied, adapted, translated, or used as a development dependency.
- The first Android release will be a signed APK distributed from the HTSMS website. Google Play review is a parallel workstream and will not block commercial launch.
- English and French are required for launch.
- Cameroon launch testing prioritizes MTN Cameroon and Orange Cameroon, plus representative Samsung, Tecno, Infinix, and Xiaomi devices.

## 2. Planning assumptions

The 20-week launch schedule assumes the following minimum delivery capacity:

| Role | Planned allocation | Primary responsibility |
|---|---:|---|
| Product owner/business lead | 0.5 FTE | Decisions, pricing, legal/commercial approvals |
| Technical lead/backend engineer | 1.0 FTE | Architecture, API, messaging, security |
| Android engineer | 1.0 FTE | Gateway app, SMS operations, device reliability |
| Frontend engineer | 1.0 FTE | Dashboard, admin console, localization |
| QA/automation engineer | 0.75 FTE from Week 3 | Test automation, device/carrier test matrix |
| DevOps/security engineer | 0.5 FTE | Infrastructure, CI/CD, monitoring, hardening |
| UX/UI designer | 0.5 FTE through Week 8 | Product flows and responsive UI |
| Legal/privacy adviser | Milestone-based | Cameroon telecom, privacy, terms, marketing review |

If one person performs all engineering roles, the expected commercial launch moves from December 2026 to approximately April-May 2027. Dates below are baseline commitments subject to timely business decisions, Firebase/domain access, test phones/SIMs, and legal review.

## 3. Environments and release strategy

| Environment | Target date | Purpose |
|---|---|---|
| Local Docker development | 9 August 2026 | Reproducible engineering environment |
| Shared development | 16 August 2026 | Continuous integration and feature testing |
| Staging | 30 August 2026 | Production-like integration and QA |
| Private alpha | 27 September 2026 | Internal end-to-end validation |
| Controlled customer beta | 18 October 2026 | Selected external customers |
| Production | 13 December 2026 | Paid public launch |

Proposed public endpoints:

- `https://htsms.cm-ea.com` — website and customer dashboard
- `https://api.htsms.cm-ea.com` — public REST API
- `https://docs.htsms.cm-ea.com` — developer documentation
- `https://status.htsms.cm-ea.com` — public service status
- `https://htsms.cm-ea.com/download` — signed Android APK, checksum, and installation guide

## 4. Milestones and hard gates

| ID | Milestone | Deadline | Exit condition |
|---|---|---:|---|
| M0 | Scope and clean-room controls approved | 2 Aug 2026 | Requirements, ownership, licence policy, risks approved |
| M1 | Architecture and UX foundation approved | 9 Aug 2026 | ADRs, data model, API draft, prototypes approved |
| M2 | Development platform operational | 23 Aug 2026 | CI/CD, dev environment, auth/tenancy skeleton working |
| M3 | First phone-to-server connection | 30 Aug 2026 | Paired Android phone sends authenticated heartbeat |
| M4 | First end-to-end outbound SMS | 13 Sep 2026 | API request reaches phone and status returns to server |
| M5 | Transactional gateway feature-complete | 27 Sep 2026 | Send, receive, delivery, retries, dual-SIM, webhooks working |
| M6 | Private alpha passed | 11 Oct 2026 | Security, recovery, load, localization, device tests pass |
| M7 | Controlled external beta begins | 18 Oct 2026 | 3-5 approved businesses onboarded |
| M8 | Marketing module feature-complete | 8 Nov 2026 | Contacts, consent, campaigns, suppression, analytics working |
| M9 | Commercial operations ready | 22 Nov 2026 | Billing, policies, support, monitoring and runbooks ready |
| M10 | Release candidate approved | 6 Dec 2026 | No open launch-blocking defects; go/no-go signed |
| M11 | Commercial launch | 13 Dec 2026 | Paid subscriptions enabled; production SLA monitoring active |
| M12 | Stabilization review complete | 10 Jan 2027 | Launch issues resolved and Phase 2 roadmap approved |

## 5. Detailed weekly execution plan

### Week 0 — Mobilization: 21-26 July 2026

**Product and governance**

- Name the product owner and technical decision-maker.
- Establish project communication, issue tracking, document repository, and change-control process.
- Confirm the brand spelling `HTSMS` and reserve product identifiers.
- Confirm target customer types: SMEs, developers, CRM users, schools, clinics, retailers, or other verticals.
- List prohibited customer categories and prohibited message content.
- Create the initial risk, assumption, issue, and dependency log.
- Establish severity definitions for defects and incidents.

**Access and procurement**

- Provide access to DNS for `cm-ea.com`.
- Create organization-owned Firebase, cloud hosting, error-monitoring, transactional-email, and source-control accounts.
- Procure at least eight test Android phones or confirm access to them.
- Procure active MTN Cameroon and Orange Cameroon test SIMs with appropriate SMS bundles.
- Create organization-owned Android signing-key storage and backup procedure.

**Deliverables by 26 July**

- Named owners and communication channels.
- Access checklist.
- Test-device procurement list.
- Initial project risk register.

### Week 1 — Product definition and compliance discovery: 27 July-2 August 2026

**Product**

- Write the product requirements document for transactional and marketing use cases.
- Define personas: account owner, organization administrator, developer, campaign manager, support agent, and platform administrator.
- Define subscription candidates, device limits, user limits, monthly message accounting, and fair-use rules.
- Define the exact MVP boundary and explicitly defer MMS, WhatsApp, RCS, shared short codes, and iOS gateway support unless separately approved.
- Define functional service-level objectives for API availability, queue latency, webhook delivery, and support response.

**Clean-room/IP controls**

- Record permitted references: public product behaviour, public documentation, protocol standards, Android documentation, and original HTSMS specifications.
- Prohibit copying from the httpSMS repository, APK decompilation, source translations, or dependency reuse.
- Require each contributor to acknowledge the clean-room policy.
- Create an independent architecture and naming scheme.

**Legal and compliance**

- Commission a Cameroon legal review covering electronic communications, electronic commerce, privacy, consumer protection, direct marketing, and carrier/SIM terms.
- Determine whether HTSMS needs an authorization, declaration, registration, or contractual relationship with ART or carriers.
- Define controller/processor roles between HTSMS and subscribers.
- Draft consent, opt-out, data-retention, lawful-use, and customer-verification requirements.

**Gate M0 — 2 August**

- Product scope signed off.
- Clean-room policy signed off.
- No unresolved legal issue that makes the intended service clearly unviable.

### Week 2 — Architecture, data model, API, and UX: 3-9 August 2026

**Architecture**

- Write architecture decision records for Laravel/PHP, Nuxt/Vue, Kotlin, PostgreSQL, Redis, Firebase Cloud Messaging, object storage, Cloudflare, and hosting.
- Define multi-tenant boundaries and authorization strategy.
- Define the outbound/inbound message state machines.
- Define message ordering, retries, expiration, deduplication, and idempotency behaviour.
- Define device pairing, credential rotation, revocation, and lost-device handling.
- Define transactional and marketing queue separation.
- Define data encryption, secrets, backups, retention, deletion, and audit logging.

**API**

- Draft OpenAPI 3.1 endpoints for authentication, organizations, devices, messages, webhooks, contacts, campaigns, templates, usage, and billing.
- Define error codes and normalized device/carrier failure reasons.
- Define webhook schemas, signing headers, retry schedule, and replay protection.
- Define public API versioning and backward-compatibility policy.

**UX**

- Produce responsive wireframes for onboarding, phone pairing, dashboard, compose message, message detail, conversations, API keys, webhooks, contacts, campaigns, billing, and administration.
- Produce French and English terminology glossary.
- Complete an accessibility review of the proposed navigation and core forms.

**Gate M1 — 9 August**

- Architecture, schema, API contract, and core UX flows approved.

### Week 3 — Engineering foundation: 10-16 August 2026

**Repositories and quality**

- Create separate or clearly isolated backend, web, Android, infrastructure, and documentation modules.
- Configure branch protection, pull-request checks, secret scanning, dependency scanning, formatting, linting, and automated tests.
- Establish semantic versioning and release-note conventions.
- Add a proprietary licence and copyright notices.
- Add contributor clean-room attestations.

**Infrastructure**

- Build reproducible Docker development services for API, worker, scheduler, PostgreSQL, Redis, mail capture, and object storage.
- Create development cloud environment and secrets store.
- Establish database migration and seed-data workflows.
- Configure automated encrypted backups and define restoration procedure.

**Application foundation**

- Implement organization-aware authentication skeleton.
- Implement organization, user, role, permission, subscription-plan, and audit-event foundations.
- Add English/French localization framework.
- Create Android project, build variants, CI build, signing configuration, and secure local storage abstraction.

**Deliverables by 16 August**

- All modules build in CI.
- A developer can start the full local stack from documented instructions.
- Initial automated tests pass.

### Week 4 — Identity, tenancy, security baseline, and device registration: 17-23 August 2026

**Backend/web**

- Implement registration, login, logout, email verification, forgotten password, password reset, session management, and rate limiting.
- Implement organizations, invitations, roles, and tenant-scoped authorization.
- Implement API-key creation, prefix display, hashing, scopes, rotation, expiry, and revocation.
- Implement security audit events for authentication and access changes.
- Implement initial dashboard shell and onboarding checklist.

**Android/backend device work**

- Define device record, SIM-slot record, device capability document, and heartbeat schema.
- Implement short-lived QR pairing challenge.
- Implement device credential issuance and revocation.
- Ensure QR tokens are one-time-use, expire quickly, and never contain reusable user credentials.

**Testing**

- Add tenant-isolation tests for every implemented resource.
- Add authentication abuse, credential rotation, and expired-token tests.
- Complete first lightweight threat-model workshop.

**Gate M2 — 23 August**

- Authentication and tenant isolation pass automated tests.
- CI deploys the development environment.

### Week 5 — Android pairing, permissions, and connectivity: 24-30 August 2026

**Android**

- Implement QR scanner and manual pairing-code fallback.
- Implement prominent permission disclosure and step-by-step setup.
- Request only permissions required for the current gateway functions.
- Implement SIM discovery, active subscription identification, default SIM selection, and capability reporting.
- Implement authenticated heartbeat with app version, Android version, device status, battery state, network state, and SIM state.
- Implement foreground/background execution strategy and battery-optimization guidance.
- Implement secure credential storage and remote logout/revocation.

**Backend/dashboard**

- Implement device list, device detail, rename, revoke, and online/offline state.
- Implement heartbeat expiry and offline alerts.
- Record device security and configuration changes in audit logs.

**Gate M3 — 30 August**

- A newly installed Android phone pairs without manually copying a permanent secret.
- Dashboard accurately reports connectivity and SIM capability.

### Week 6 — Outbound SMS pipeline: 31 August-6 September 2026

**API/backend**

- Implement `POST /v1/messages`, message validation, E.164 normalization, client request ID, and idempotency key.
- Implement device/SIM assignment and transactional queue.
- Implement per-organization, API-key, device, SIM, and recipient rate limits.
- Implement message expiration and maximum attempt rules.
- Send Firebase wake-up notifications containing no plaintext message content.
- Implement authenticated device fetch/lease protocol with lease expiry.

**Android**

- Fetch leased messages after push notification or periodic recovery sync.
- Send single-part and multipart SMS through the selected SIM.
- Report accepted, sending, sent, and failed events.
- Prevent duplicate sending after process death, reconnect, or repeated push notification.

**QA**

- Test valid/invalid Cameroon and international numbers.
- Test ASCII, GSM-7 extension characters, Unicode, and multipart messages.
- Test phone offline, server offline, lost acknowledgement, and duplicate API submission.

**Deliverable by 6 September**

- Reliable outbound message in development with traceable state events.

### Week 7 — Delivery reports, retries, and scheduling: 7-13 September 2026

- Capture Android sent and delivery intents and correlate them with message attempts.
- Normalize platform and carrier results into stable public failure codes.
- Implement retry policy for temporary failures and terminal handling for permanent failures.
- Implement scheduled sending with customer timezone and UTC storage.
- Implement queue back-pressure and configurable messages-per-minute limits.
- Implement message detail timeline in dashboard.
- Add administrative trace view without exposing another tenant’s data.
- Add metrics for queue depth, dispatch latency, attempt count, success, failure, expiration, and device offline duration.

**Gate M4 — 13 September**

- An authenticated API request sends through a selected customer SIM and returns complete status history without duplicates.

### Week 8 — Incoming SMS and webhooks: 14-20 September 2026

**Android/inbound**

- Capture new inbound SMS with sender, recipient/SIM, timestamp, multipart reconstruction, and stable device event ID.
- Upload inbound events through authenticated, idempotent endpoint.
- Handle offline buffering and later synchronization.
- Avoid uploading unrelated historical inbox messages.

**Backend/dashboard**

- Store inbound messages and construct basic conversations.
- Implement webhook endpoint CRUD and event subscriptions.
- Implement webhook signing, timestamp validation, delivery logs, exponential retries, manual replay, and automatic endpoint disabling.
- Implement `message.received`, `message.sent`, `message.delivered`, `message.failed`, and `message.expired` events.
- Add webhook test-event facility.

**QA**

- Test multipart incoming SMS, repeated broadcasts, offline upload, webhook timeouts, redirects, invalid TLS, and replay attempts.

### Week 9 — Transactional dashboard and operational controls: 21-27 September 2026

- Complete dashboard overview with device health, recent traffic, success rate, and usage.
- Implement compose/send, scheduling, SIM selection, and test-message flows.
- Implement message search and filters by number, status, device, SIM, date, direction, and request ID.
- Implement conversation view with inbound/outbound messages.
- Implement export with authorization, row limits, and audit logging.
- Add organization-level sending limits and emergency pause.
- Add platform-admin account/device/API-key suspension.
- Add user-facing explanations for delivery limitations and carrier dependency.

**Gate M5 — 27 September**

- Transactional feature set is complete in staging.
- No known cross-tenant access defect.

### Week 10 — Reliability, recovery, and private alpha: 28 September-4 October 2026

- Run 1,000-message queued workload with controlled device rate.
- Test API, worker, Redis, PostgreSQL, Android process, phone, and network restarts.
- Test delayed push, lost push, duplicate push, lost status acknowledgement, and expired lease recovery.
- Verify no duplicate carrier send under every recovery case.
- Test SIM removal, insufficient airtime, airplane mode, phone reboot, revoked device, and expired credentials.
- Run database backup restoration into an isolated environment.
- Validate alerts for queue age, offline devices, webhook backlog, failure spikes, database health, and certificate expiry.
- Begin internal private alpha with business-owned phones.

### Week 11 — Security, privacy, and localization hardening: 5-11 October 2026

- Perform application threat-model review covering tenant isolation, phone impersonation, API-key theft, QR interception, webhook SSRF, replay, spam, and administrative abuse.
- Run static analysis, dependency review, API authorization tests, and Android storage/log inspection.
- Prevent webhooks from reaching private/internal network ranges.
- Redact phone numbers, tokens, and message content from normal logs.
- Implement retention and deletion jobs.
- Implement organization data export and deletion-request workflow.
- Complete English and French translations for all launch-critical screens, emails, API errors, and Android setup instructions.
- Complete accessibility and responsive-device QA.
- Resolve all Severity 1 and Severity 2 alpha defects.

**Gate M6 — 11 October**

- Private alpha acceptance suite passes.
- Security owner approves beta exposure.

### Week 12 — Controlled external beta: 12-18 October 2026

- Select 3-5 verified beta businesses with different phone models and use cases.
- Execute business verification and beta agreements.
- Provide guided onboarding and confirm carrier/SIM responsibility.
- Instrument onboarding completion, pairing failure, time-to-first-message, queue latency, delivery status, and webhook success.
- Establish daily beta health review and named incident contact.
- Limit each beta account’s devices, messages per minute, daily volume, and destinations.
- Collect structured customer feedback without expanding scope during the beta unless required for correctness or safety.

**Gate M7 — 18 October**

- All beta customers can pair a phone and complete an end-to-end transactional integration.

### Week 13 — Marketing foundation: 19-25 October 2026

- Implement contacts, custom fields, lists, tags, and tenant-safe searching.
- Implement CSV import with preview, E.164 normalization, duplicate handling, invalid-row report, and maximum file size.
- Implement consent source, consent timestamp, collection method, purpose, and evidence fields.
- Implement global and list-level suppression records.
- Implement manual unsubscribe and inbound STOP/ARRET keyword processing, including French variants approved by policy.
- Prevent suppressed recipients from being messaged through marketing endpoints.
- Keep transactional exceptions narrowly defined and audited.

### Week 14 — Campaign creation and scheduling: 26 October-1 November 2026

- Implement reusable templates and approved variables.
- Implement campaign draft, audience selection, preview, estimated recipients, SMS segment count, test send, approval, scheduling, pause, resume, and cancel.
- Implement quiet hours using recipient or organization timezone rules.
- Implement campaign-specific rate limits and fair scheduling across customers/devices.
- Implement recipient-level idempotency and duplicate prevention.
- Separate marketing workers and queues from transactional workers and queues.
- Implement campaign audit trail and approval attribution.

### Week 15 — Marketing analytics and abuse controls: 2-8 November 2026

- Implement campaign counts for queued, dispatched, sent, delivered, failed, expired, and opted out.
- Implement recipient result export and failure categorization.
- Implement failure-rate, unsubscribe-rate, complaint, unusual-volume, and destination-pattern alerts.
- Implement progressive sending limits for new accounts.
- Implement business-verification status and campaign restrictions.
- Implement prohibited-content flags for manual review; do not silently modify customer text.
- Add platform-admin campaign pause and account quarantine.
- Test that marketing load does not breach transactional latency objectives.

**Gate M8 — 8 November**

- A consented contact list can be imported, deduplicated, campaigned, throttled, reported, and unsubscribed safely.

### Week 16 — Billing, plans, and commercial administration: 9-15 November 2026

- Finalize Free/Trial, Starter, Business, and higher-volume plan definitions.
- Meter active devices, API usage, messages submitted, message parts, storage, contacts, and campaign volume as required by pricing.
- Integrate the approved payment provider or implement invoice/manual activation if provider selection is pending.
- Implement trial start/end, upgrade, downgrade, grace period, payment failure, cancellation, and account closure.
- Ensure plan enforcement never causes a previously accepted message to disappear silently.
- Implement invoices/receipts and French/English billing communications.
- Implement support tools that do not expose plaintext secrets or unnecessary message content.

### Week 17 — Policies, support, documentation, and operations: 16-22 November 2026

**Customer-facing documents**

- Finalize Terms of Service, Privacy Notice, Data Processing Agreement, Acceptable Use Policy, Marketing/Consent Policy, Cookie Notice, refund/cancellation terms, and carrier/SIM disclaimer.
- Publish security, retention, deletion, vulnerability-reporting, and subprocessors information.

**Documentation**

- Publish Android installation/pairing/troubleshooting guide.
- Publish API quick start, authentication, idempotency, errors, webhook verification, pagination, rate limits, and SDK examples.
- Publish campaign consent and opt-out guide.
- Publish service limits and status definitions.

**Operations**

- Create incident, suspected abuse, compromised key, lost phone, data request, account recovery, webhook failure, and carrier outage runbooks.
- Establish support intake, priority levels, escalation path, and coverage hours.
- Train support and platform administrators.

**Gate M9 — 22 November**

- Commercial, support, billing, policy, and operational readiness review passes.

### Week 18 — Production readiness and penetration testing: 23-29 November 2026

- Freeze nonessential feature development.
- Conduct independent or separately owned penetration testing of API, web, tenant isolation, device protocol, webhooks, and administration.
- Run load tests for expected launch traffic plus agreed safety margin.
- Run disaster-recovery exercise and measure recovery time and recovery point.
- Rotate staging secrets and verify production secret provisioning.
- Validate WAF, DDoS/rate limits, TLS configuration, security headers, email authentication, backup alerts, and administrative MFA.
- Verify Android release signing, APK reproducibility where practical, checksum publication, and rollback procedure.
- Resolve all launch-blocking findings.

### Week 19 — Release candidate and go/no-go: 30 November-6 December 2026

- Cut version `1.0.0-rc.1` for backend, dashboard, and Android app.
- Execute the complete regression matrix.
- Run final tests on MTN and Orange SIMs and representative Samsung, Tecno, Infinix, and Xiaomi devices.
- Validate subscriptions in payment sandbox and controlled production transaction.
- Validate all customer emails and French/English pages.
- Validate monitoring, paging, dashboards, public status page, support contacts, and incident roles.
- Confirm legal approval and publish required policies.
- Perform database migration and rollback rehearsal.
- Hold formal go/no-go meeting by 6 December.

**Gate M10 — 6 December**

- Zero open Severity 1 defects.
- Zero unresolved security findings rated high/critical.
- Severity 2 defects require explicit documented launch acceptance.
- Product, engineering, operations, security, support, and business owner sign off.

### Week 20 — Commercial launch: 7-13 December 2026

- Deploy production schema and services using the rehearsed procedure.
- Publish signed HTSMS Android APK, checksum, certificate fingerprint, release notes, privacy disclosure, and installation guide.
- Enable production registration and paid subscriptions in controlled stages.
- Increase customer and campaign limits gradually, not all at once.
- Monitor API errors, queue delay, device connectivity, sending failures, webhook health, signups, payments, and abuse indicators continuously during launch window.
- Hold daily launch review and publish incidents on the status page.
- Preserve rollback capability throughout the launch window.

**Gate M11 — 13 December**

- HTSMS is commercially available at `htsms.cm-ea.com`.

### Stabilization — 14 December 2026-10 January 2027

- Prioritize production defects, security issues, data correctness, billing correctness, and onboarding problems over new features.
- Review every account suspension, abuse alert, opt-out failure, message duplicate, and customer-impacting incident.
- Compare production service objectives against actual results.
- Run a post-launch security and privacy review.
- Review customer feedback and adoption funnels.
- Produce the next-quarter roadmap for SDKs, integrations, reseller support, MMS, advanced analytics, and Google Play distribution.

**Gate M12 — 10 January 2027**

- Stabilization report and Phase 2 roadmap approved.

## 6. Continuous workstreams

### Quality assurance

- Unit tests accompany business logic and state transitions.
- Integration tests cover PostgreSQL, Redis, queues, Firebase abstraction, webhooks, and device protocol.
- Contract tests ensure Android and backend versions remain compatible.
- End-to-end tests cover registration through delivery and inbound reply.
- Every defect receives severity, reproduction steps, owner, target release, and regression test.
- No milestone passes with an unresolved Severity 1 defect.

### Security and privacy

- Threat model is updated at every material architecture change.
- Secrets never enter source control or ordinary logs.
- Production access uses named accounts, least privilege, MFA, and audit logging.
- API keys are hashed and displayed once.
- Phone credentials are device-bound, revocable, and rotatable.
- Data retention and deletion are automated and tested.
- Subprocessors and cross-border data locations are recorded before launch.

### Clean-room compliance

- No developer uses the httpSMS source repository as an implementation reference.
- Public documentation may inform desired behaviour, but HTSMS uses independently created designs, types, schemas, names, and code.
- Third-party dependencies receive licence review before adoption.
- Proprietary notices and a software bill of materials are produced for releases.

### Product and scope control

- Weekly product review accepts, rejects, or defers scope changes.
- New requests are assessed against the launch critical path.
- Launch-blocking work is limited to correctness, reliability, security, compliance, billing, support, and essential usability.
- Deferred enhancements go to the post-launch backlog.

## 7. Launch acceptance criteria

HTSMS may launch only when all of the following are true:

### Functional

- A customer can register, subscribe, pair a phone, create an API key, send, receive, and inspect an SMS.
- Dual-SIM selection works on supported test devices.
- Scheduled, expired, failed, sent, and delivered states are correctly represented.
- Webhooks are signed, retryable, inspectable, and replayable.
- A marketer can import consented contacts, send a throttled campaign, and honor opt-outs.
- French and English launch-critical paths are complete.

### Reliability

- A 1,000-message controlled queue completes without lost or duplicate carrier sends.
- Phone and server restarts recover safely.
- Lost and duplicate push notifications do not cause duplicate SMS.
- Backup restoration and deployment rollback have been rehearsed.
- Monitoring detects queue, device, API, webhook, database, and certificate failures.

### Security

- Tenant-isolation test suite passes.
- No open high/critical penetration-test finding.
- Administrative MFA and audit logs are active.
- API keys and device credentials can be revoked immediately.
- Logs do not expose secrets or unnecessary message content.

### Commercial and compliance

- Pricing, billing, cancellation, and plan enforcement work in production.
- Terms, privacy notice, DPA, AUP, marketing policy, and carrier disclaimer are published.
- Business verification and abuse-suspension processes are operational.
- Consent evidence, suppression, STOP/ARRET handling, and deletion workflows are tested.
- Cameroon regulatory and carrier-terms review is completed and documented.

## 8. Decision deadlines

| Decision | Owner | Required by | Schedule impact if late |
|---|---|---:|---|
| Hosting region/provider | Business + technical lead | 2 Aug 2026 | Blocks infrastructure |
| Subscription pricing model | Business owner | 9 Aug 2026 | Blocks billing and usage design |
| Payment provider | Business owner | 30 Aug 2026 | Blocks Week 16 |
| SMS retention defaults | Business + privacy adviser | 9 Aug 2026 | Blocks schema/privacy design |
| Supported Android minimum version | Technical lead | 9 Aug 2026 | Blocks Android architecture |
| Test devices and SIMs available | Business owner | 23 Aug 2026 | Blocks field validation |
| Legal/regulatory preliminary opinion | Legal adviser | 30 Aug 2026 | Can block external beta |
| Final pricing and plan limits | Business owner | 18 Oct 2026 | Blocks commercial configuration |
| Final policies/legal approval | Legal adviser | 22 Nov 2026 | Blocks production launch |
| Go/no-go approval | All accountable owners | 6 Dec 2026 | Blocks launch |

## 9. Immediate actions

The following actions begin the project:

1. Appoint the business product owner and technical lead by 24 July 2026.
2. Provide DNS, Firebase, hosting, source-control, email, and monitoring account access by 26 July 2026.
3. Confirm the delivery team and allocations by 26 July 2026.
4. Procure or allocate the Android test-device and Cameroon SIM matrix by 2 August 2026.
5. Start the Cameroon regulatory, privacy, marketing, and carrier-terms review by 27 July 2026.
6. Approve the requirements, clean-room policy, architecture, data model, and API contract no later than 9 August 2026.

