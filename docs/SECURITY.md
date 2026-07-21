# HTSMS security and vulnerability reporting

Report suspected vulnerabilities privately to `security@cm-ea.com`. Do not include customer message content, credentials, or unnecessary personal data. Provide the component, safe reproduction, and impact. Do not perform denial of service, social engineering, persistence, destruction, or access another customer’s data.

HTSMS enforces organization authorization, hashes API/device credentials, encrypts sensitive gateway/webhook values, uses one-time pairing, supports immediate revocation, signs webhooks, blocks private webhook destinations, rate-limits authentication/API traffic, records audit/state events, and applies browser security headers.

Production still requires independent penetration testing, administrator MFA, centralized redacted logs, backup-restore rehearsal, Android release signing, SBOM publication, and legal/regulatory approval before public launch.
