# HTSMS operations runbook

Monitor API 5xx/429 rates, oldest queued message, assignment age, phones offline over two minutes, failures by carrier/device, webhook backlog, PostgreSQL connections/storage, Redis memory, worker restarts, and TLS expiry.

## Lost or compromised phone

Revoke the device immediately and confirm all device credentials are revoked. Pair again only after the phone/SIM is secured. Review nearby assignments. Dispatched-but-unacknowledged messages remain failed/unknown to avoid duplicates.

## Compromised API key or webhook secret

Revoke/rotate the API key. Disable the webhook endpoint, create a replacement secret, and replay only verified events. Search audit records using identifiers, never plaintext secrets.

## Carrier/device outage

Pause the workspace when queue growth could cause stale delivery. Preserve accepted work, restore connectivity, verify heartbeat/SIM inventory, send a controlled test, then resume. Never requeue `dispatched` messages without independent proof they were not sent.

## Webhook backlog

Check DNS, TLS, and response codes. Contact the customer before re-enabling a repeatedly failing endpoint. Replay the stored event after resolution.

## Incident severity

- SEV1: cross-tenant exposure, secret compromise, uncontrolled duplicates, material outage
- SEV2: one customer blocked, billing corruption, sustained delivery/webhook failure
- SEV3: degraded noncritical feature with workaround

For SEV1: appoint an incident lead, stop harmful processing, preserve evidence, notify business/privacy/security owners, publish updates, and keep a timestamped log. Complete a review with owners and deadlines.
