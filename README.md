# HTSMS

HTSMS is a proprietary subscription SaaS that turns customer-owned Android phones and SIM cards into tenant-isolated programmable SMS gateways.

## Components

- `backend/` — Laravel API, customer portal, platform administration, queues, scheduler, subscriptions, inbound SMS, and signed webhooks
- `android/` — native Kotlin gateway with Android Keystore and encrypted offline buffering
- `deploy/` — production containers, PostgreSQL, Redis, workers, automatic TLS, and environment template
- `docs/` — API, Android setup, deployment, operations, security, architecture, clean-room policy, and project schedule
- `HTSMS_PROJECT_TIMELINE.md` — dated delivery baseline and launch gates

## Backend development

Requirements: PHP 8.3+, Composer 2.9+, Node.js 22+, and SQLite or PostgreSQL.

```powershell
Set-Location backend
composer install
Copy-Item .env.example .env
php artisan key:generate
npm install
php artisan migrate
php artisan test
npm run build
```

Quality gates include `vendor/bin/phpstan analyse --memory-limit=1G`, `vendor/bin/pint --test`, `composer audit`, `npm audit --audit-level=high`, and the browser smoke test.

Android builds with JDK 17 and SDK 35 using `android/gradlew testDebugUnitTest assembleDebug lintDebug`.

## Production

Start with [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) and `deploy/.env.production.example`. Production launch remains gated on the requirements documented in the project timeline and security guide.

All source is proprietary. Redistribution, modification, sublicensing, and hosted resale are not granted except by a separate written agreement. Do not copy or use httpSMS/httpsms source code; see `docs/CLEAN_ROOM_POLICY.md`.
