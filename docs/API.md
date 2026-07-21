# HTSMS API quick start

Base URL: `https://htsms.cm-ea.com/api/v1`. Create an organization-scoped key in **Developer settings** and send `Authorization: Bearer htsms_live_…`.

```bash
curl -X POST https://htsms.cm-ea.com/api/v1/messages \
  -H "Authorization: Bearer $HTSMS_API_KEY" \
  -H "Idempotency-Key: order-1001" \
  -H "Content-Type: application/json" \
  -d '{"to":"+237670000000","content":"Your order is ready."}'
```

Use E.164 destinations. Reuse an idempotency key only for the same logical request; replay returns the original message without consuming allowance twice. Statuses include `scheduled`, `queued`, `assigned`, `dispatched`, `sent`, `delivered`, `retry_pending`, `failed`, `expired`, and `cancelled`.

Abilities include `messages:read`, `messages:write`, `contacts:read`, `contacts:write`, `campaigns:read`, `campaigns:write`, `webhooks:read`, and `webhooks:write`. Common errors: 401 invalid credential, 403 missing ability, 402 subscription restriction, 404 hidden/missing resource, 409 invalid state, 422 invalid input, 429 rate limit.

## Contacts and campaigns

Use `GET|POST /contacts` to manage consent-aware recipients and `GET|POST /campaigns` to launch a campaign. Campaigns accept 1-1,000 contact IDs. Only contacts with recorded consent and no organization suppression are queued; every excluded recipient remains in the campaign snapshot with its reason. Opt-outs are durable and are not cleared merely by changing the contact record.

Inbound replies matching `STOP`, `UNSUBSCRIBE`, `CANCEL`, `END`, `QUIT`, `ARRET`, or `ARRÊT` (case-insensitive exact keywords) immediately suppress the sender and update a matching contact to opted out.

## Webhook verification

Read `HTSMS-Event-ID`, `HTSMS-Event-Type`, `HTSMS-Timestamp`, and `HTSMS-Signature`. Compute `HMAC-SHA256(timestamp + "." + raw_body, signing_secret)` and constant-time compare it with the `v1=` signature. Reject stale timestamps and persist event IDs against replay. Return 2xx promptly.

Endpoints must be public HTTPS and cannot resolve to private/reserved ranges. Attempts are inspectable and failed events replayable.
